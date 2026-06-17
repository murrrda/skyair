<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-3 years', '-1 month');

        return [
            'zaposlen_user_id' => fn () => Zaposlen::factory()->create([
                'user_id' => User::factory(),
                'role' => 'pilot',
                'datum_zaposlenja' => now()->subYears(2)->toDateString(),
                'status' => 'aktivan',
            ])->user_id,
            'certificate_type_id' => CertificateType::factory(),
            'issued_at' => $issuedAt->format('Y-m-d'),
            'expires_at' => fake()->dateTimeBetween('+1 month', '+3 years')->format('Y-m-d'),
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'issued_at' => now()->subYears(3)->toDateString(),
            'expires_at' => now()->subMonth()->toDateString(),
        ]);
    }
}
