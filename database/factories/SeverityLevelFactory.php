<?php

namespace Database\Factories;

use App\Models\SeverityLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeverityLevel>
 */
class SeverityLevelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'description' => fake()->sentence(),
            'rank' => fake()->numberBetween(1, 4),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
