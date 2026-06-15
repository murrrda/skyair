<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketFieldExtractor
{
    private string $driver;

    private float $confidenceThreshold;

    public function __construct()
    {
        $this->driver = config('services.nlp.driver');
        $this->confidenceThreshold = config('services.nlp.confidence_threshold');
    }

    public function modelUsed(): string
    {
        return match ($this->driver) {
            'ollama' => config('services.ollama.model'),
            default => config('services.gemini.model'),
        };
    }

    /**
     * @return array{fields: array<string, array{value: ?string, confidence: float}>, raw_response: string}
     */
    public function extract(Category $category, string $description): array
    {
        $category->loadMissing('fields');

        if ($category->fields->isEmpty()) {
            return ['fields' => [], 'raw_response' => ''];
        }

        $prompt = $this->buildPrompt($category->fields, $description);

        $parsed = match ($this->driver) {
            'ollama' => $this->callOllama($prompt),
            default => $this->callGemini($prompt),
        };

        $results = [];
        foreach ($category->fields as $field) {
            $extracted = $parsed[$field->field_name] ?? null;
            $value = $extracted['value'] ?? null;
            $confidence = (float) ($extracted['confidence'] ?? 0);

            if ($confidence < $this->confidenceThreshold) {
                $value = null;
            }

            $results[$field->field_name] = [
                'value' => $value,
                'confidence' => $confidence,
            ];
        }

        return [
            'fields' => $results,
            'raw_response' => json_encode($parsed, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function buildPrompt(Collection $fields, string $description): string
    {
        $fieldSpecs = $fields->map(function ($field) {
            $spec = "- {$field->field_name} (type: {$field->field_type}, "
                .($field->required ? 'required' : 'optional')
                ."): {$field->prompt}";

            if ($field->validation_rule) {
                $spec .= " Validation pattern: {$field->validation_rule}";
            }
            if ($field->reference_table) {
                $spec .= " This is a reference to the {$field->reference_table} table.";
            }

            return $spec;
        })->implode("\n");

        return <<<PROMPT
You are a data extraction assistant for an airline support ticket system.
Analyze the customer's problem description and extract the required fields.

Fields to extract:
{$fieldSpecs}

Customer's description:
"{$description}"

Instructions:
- Extract each field value from the description. If a field is not mentioned or unclear, set value to null.
- For datetime fields, normalize to ISO 8601 format (YYYY-MM-DDTHH:MM:SS).
- For reference fields (flight numbers, baggage numbers), extract the identifier as-is from the text.
- Assign a confidence score (0.0 to 1.0) for each extraction. Use 0.0 if the field is not found.
- The description may be in Serbian/Bosnian — handle both Latin and Cyrillic scripts.

Respond ONLY with a valid JSON object (no markdown, no explanation) in this exact format:
{
  "field_name": {"value": "extracted value or null", "confidence": 0.95},
  ...
}
PROMPT;
    }

    /**
     * @return array<string, array{value: ?string, confidence: float}>
     */
    private function callOllama(string $prompt): array
    {
        $host = config('services.ollama.host');
        $model = config('services.ollama.model');

        try {
            $response = Http::timeout(60)
                ->post("{$host}/api/chat", [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'format' => 'json',
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.1,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Ollama API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $text = $response->json('message.content', '');
            $decoded = json_decode($text, true);

            if (! is_array($decoded)) {
                Log::warning('Ollama returned invalid JSON', ['text' => $text]);

                return [];
            }

            return $decoded;
        } catch (ConnectionException $e) {
            Log::error('Ollama connection failed', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<string, array{value: ?string, confidence: float}>
     */
    private function callGemini(string $prompt): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::timeout(30)
                ->retry(3, 1000, throw: false)
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');

            $decoded = json_decode($text, true);

            if (! is_array($decoded)) {
                Log::warning('Gemini returned invalid JSON', ['text' => $text]);

                return [];
            }

            return $decoded;
        } catch (ConnectionException $e) {
            Log::error('Gemini connection failed', ['message' => $e->getMessage()]);

            return [];
        }
    }
}
