<?php

namespace Tests\Feature;

use App\Models\IncidentType;
use App\Models\SeverityLevel;
use Database\Seeders\IncidentTypeSeeder;
use Database\Seeders\SeverityLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_type_seeder_populates_the_catalogue(): void
    {
        $this->seed(IncidentTypeSeeder::class);

        $this->assertSame(6, IncidentType::count());
        $this->assertDatabaseHas('incident_types', ['name' => 'Tehnički kvar']);
    }

    public function test_severity_level_seeder_populates_ranked_catalogue(): void
    {
        $this->seed(SeverityLevelSeeder::class);

        $this->assertSame(4, SeverityLevel::count());
        $this->assertSame(
            ['Low', 'Medium', 'High', 'Critical'],
            SeverityLevel::orderBy('rank')->pluck('name')->all()
        );
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed(IncidentTypeSeeder::class);
        $this->seed(IncidentTypeSeeder::class);
        $this->seed(SeverityLevelSeeder::class);
        $this->seed(SeverityLevelSeeder::class);

        $this->assertSame(6, IncidentType::count());
        $this->assertSame(4, SeverityLevel::count());
    }

    public function test_active_scopes_return_only_active_rows(): void
    {
        IncidentType::factory()->create(['is_active' => true]);
        IncidentType::factory()->create(['is_active' => false]);
        SeverityLevel::factory()->create(['is_active' => true]);
        SeverityLevel::factory()->create(['is_active' => false]);

        $this->assertSame(1, IncidentType::query()->active()->count());
        $this->assertSame(1, SeverityLevel::query()->active()->count());
    }
}
