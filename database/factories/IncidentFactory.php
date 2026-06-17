<?php

namespace Database\Factories;

use App\Models\Flight;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\SeverityLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flight_id' => Flight::factory(),
            'incident_type_id' => IncidentType::factory(),
            'severity_level_id' => SeverityLevel::factory(),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'description' => fake()->paragraph(),
        ];
    }
}
