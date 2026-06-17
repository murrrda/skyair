<?php

namespace Database\Factories;

use App\Models\EmployeeTraining;
use App\Models\TrainingType;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeTraining>
 */
class EmployeeTrainingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-3 years', '-1 month');
        $finishedAt = (clone $startedAt)->modify('+'.fake()->numberBetween(1, 14).' days');

        return [
            'zaposlen_user_id' => fn () => Zaposlen::factory()->create([
                'user_id' => User::factory(),
                'role' => 'pilot',
                'datum_zaposlenja' => now()->subYears(2)->toDateString(),
                'status' => 'aktivan',
            ])->user_id,
            'training_type_id' => TrainingType::factory(),
            'started_at' => $startedAt->format('Y-m-d'),
            'finished_at' => $finishedAt->format('Y-m-d'),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
