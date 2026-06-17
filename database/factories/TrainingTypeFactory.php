<?php

namespace Database\Factories;

use App\Models\TrainingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingType>
 */
class TrainingTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['type_rating', 'simulator', 'recurrent', 'safety']),
            'duration_days' => fake()->randomElement([1, 3, 5, 14, 30]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
