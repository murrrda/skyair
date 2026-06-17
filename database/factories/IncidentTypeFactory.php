<?php

namespace Database\Factories;

use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentType>
 */
class IncidentTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
