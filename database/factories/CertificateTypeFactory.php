<?php

namespace Database\Factories;

use App\Models\CertificateType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateType>
 */
class CertificateTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'default_validity_months' => fake()->randomElement([12, 24, 36, 60]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
