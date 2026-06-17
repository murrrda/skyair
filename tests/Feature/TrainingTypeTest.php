<?php

namespace Tests\Feature;

use App\Models\TrainingType;
use Database\Seeders\TrainingTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_the_catalogue(): void
    {
        $this->seed(TrainingTypeSeeder::class);

        $this->assertSame(5, TrainingType::count());
        $this->assertDatabaseHas('training_types', ['name' => 'Obuka na simulatoru — Full Flight']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(TrainingTypeSeeder::class);
        $this->seed(TrainingTypeSeeder::class);

        $this->assertSame(5, TrainingType::count());
    }

    public function test_active_scope_returns_only_active_types(): void
    {
        TrainingType::factory()->create(['is_active' => true]);
        TrainingType::factory()->create(['is_active' => false]);

        $this->assertSame(1, TrainingType::query()->active()->count());
    }
}
