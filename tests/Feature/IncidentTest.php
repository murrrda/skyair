<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Plane;
use App\Models\Route;
use App\Models\SeverityLevel;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(string $role = 'pilot'): Zaposlen
    {
        $user = User::factory()->create();

        return Zaposlen::create([
            'user_id' => $user->id,
            'role' => $role,
            'datum_zaposlenja' => now()->subYear()->toDateString(),
            'status' => 'aktivan',
        ]);
    }

    private function admin(): User
    {
        return $this->makeEmployee('admin')->user;
    }

    private function makeFlight(): Flight
    {
        $admin = $this->makeEmployee('admin')->user;
        DB::table('dispatchers')->insert([
            'user_id' => $admin->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $plane = Plane::create([
            'reg_number' => random_int(20000, 99999),
            'admin_id' => $admin->id,
            'model' => 'Airbus A320',
            'capacity' => 100,
            'luxury_level' => 2,
            'range_km' => 5000,
            'max_speed' => 800,
            'repair_service_interval' => 500,
            'model_year' => 2022,
            'status' => 'in_garage',
        ]);
        $from = Airport::create(['iata_code' => 'F'.random_int(10, 99), 'name' => 'From', 'city' => 'BEG', 'country' => 'X', 'season_type' => 'none']);
        $to = Airport::create(['iata_code' => 'T'.random_int(10, 99), 'name' => 'To', 'city' => 'CDG', 'country' => 'Y', 'season_type' => 'none']);
        $route = Route::create([
            'starting_airport_id' => $from->id,
            'landing_airport_id' => $to->id,
            'admin_id' => $admin->id,
            'name' => 'Test Route',
            'distance_km' => 500,
            'estimated_time' => 90,
            'active' => true,
        ]);
        $takeoff = now()->addDays(2)->setTime(10, 0);

        return Flight::create([
            'plane_id' => $plane->id,
            'route_id' => $route->id,
            'dispatcher_id' => $admin->id,
            'expected_takeoff' => $takeoff,
            'expected_arrival' => $takeoff->copy()->addHours(2),
            'status' => 'scheduled',
        ]);
    }

    public function test_admin_can_view_the_incident_list(): void
    {
        $this->withoutVite();
        IncidentType::factory()->create();
        SeverityLevel::factory()->create();

        $this->actingAs($this->admin())
            ->get('/admin/incidenti')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/incidenti/index', false)
                ->has('incidentTypes')
                ->has('severityLevels')
                ->has('flights')
                ->has('employees')
            );
    }

    public function test_non_admin_cannot_view_the_incident_list(): void
    {
        $this->actingAs($this->makeEmployee('pilot')->user)
            ->get('/admin/incidenti')
            ->assertForbidden();
    }

    public function test_admin_can_record_an_incident_with_responsible_employees(): void
    {
        $flight = $this->makeFlight();
        $type = IncidentType::factory()->create();
        $severity = SeverityLevel::factory()->create(['rank' => 3]);
        $emp1 = $this->makeEmployee('pilot');
        $emp2 = $this->makeEmployee('cabin_crew');

        $this->actingAs($this->admin())
            ->post('/admin/incidenti', [
                'flight_id' => $flight->id,
                'incident_type_id' => $type->id,
                'severity_level_id' => $severity->id,
                'occurred_at' => '2026-06-01 14:32',
                'description' => 'Udar ptice u motor tokom poletanja.',
                'responsible_employees' => [$emp1->user_id, $emp2->user_id],
            ])
            ->assertRedirect('/admin/incidenti');

        $incident = Incident::firstOrFail();
        $this->assertSame($flight->id, $incident->flight_id);
        $this->assertSame('Udar ptice u motor tokom poletanja.', $incident->description);
        $this->assertEqualsCanonicalizing(
            [$emp1->user_id, $emp2->user_id],
            $incident->responsibleEmployees->pluck('user_id')->all()
        );
    }

    public function test_incident_can_be_recorded_without_responsible_employees(): void
    {
        $flight = $this->makeFlight();
        $type = IncidentType::factory()->create();
        $severity = SeverityLevel::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/incidenti', [
                'flight_id' => $flight->id,
                'incident_type_id' => $type->id,
                'severity_level_id' => $severity->id,
                'occurred_at' => '2026-06-01 09:00',
                'description' => 'Kašnjenje zbog vremenskih uslova.',
            ])
            ->assertRedirect('/admin/incidenti');

        $this->assertCount(0, Incident::firstOrFail()->responsibleEmployees);
    }

    public function test_recording_requires_flight_type_severity_and_description(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/incidenti', [])
            ->assertSessionHasErrors(['flight_id', 'incident_type_id', 'severity_level_id', 'occurred_at', 'description']);
    }

    public function test_non_admin_cannot_record_an_incident(): void
    {
        $flight = $this->makeFlight();
        $type = IncidentType::factory()->create();
        $severity = SeverityLevel::factory()->create();

        $this->actingAs($this->makeEmployee('pilot')->user)
            ->post('/admin/incidenti', [
                'flight_id' => $flight->id,
                'incident_type_id' => $type->id,
                'severity_level_id' => $severity->id,
                'occurred_at' => '2026-06-01 09:00',
                'description' => 'Test',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('incidents', 0);
    }
}
