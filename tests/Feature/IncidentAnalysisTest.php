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
use App\Services\IncidentAnalysisService;
use App\Services\IncidentService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IncidentAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'incidents.analysis.window_days' => 30,
            'incidents.analysis.threshold' => 3,
        ]);
    }

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

    /**
     * @param  array<int, Zaposlen>  $employees
     */
    private function makeIncident(Flight $flight, array $employees, CarbonInterface $occurredAt): Incident
    {
        $incident = Incident::create([
            'flight_id' => $flight->id,
            'incident_type_id' => IncidentType::factory()->create()->id,
            'severity_level_id' => SeverityLevel::factory()->create()->id,
            'occurred_at' => $occurredAt,
            'description' => 'Test incident.',
        ]);
        $incident->responsibleEmployees()->sync(collect($employees)->pluck('user_id')->all());

        return $incident;
    }

    public function test_employee_is_flagged_after_reaching_the_threshold(): void
    {
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');

        $this->makeIncident($flight, [$employee], now()->subDays(20));
        $this->makeIncident($flight, [$employee], now()->subDays(10));
        $third = $this->makeIncident($flight, [$employee], now()->subDay());

        app(IncidentAnalysisService::class)->analyze($third);

        $period = $employee->periodiRizika()->whereNull('datum_kraja')->first();
        $this->assertNotNull($period);
        $this->assertSame(IncidentAnalysisService::REASON, $period->razlog->naziv);
    }

    public function test_employee_below_the_threshold_is_not_flagged(): void
    {
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');

        $this->makeIncident($flight, [$employee], now()->subDays(10));
        $second = $this->makeIncident($flight, [$employee], now()->subDay());

        app(IncidentAnalysisService::class)->analyze($second);

        $this->assertSame(0, $employee->periodiRizika()->whereNull('datum_kraja')->count());
    }

    public function test_incidents_outside_the_window_do_not_count(): void
    {
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');

        $this->makeIncident($flight, [$employee], now()->subDays(120));
        $this->makeIncident($flight, [$employee], now()->subDays(90));
        $this->makeIncident($flight, [$employee], now()->subDays(60));
        $recent = $this->makeIncident($flight, [$employee], now()->subDay());

        app(IncidentAnalysisService::class)->analyze($recent);

        $this->assertSame(0, $employee->periodiRizika()->whereNull('datum_kraja')->count());
    }

    public function test_a_second_risk_period_is_not_opened_while_one_is_active(): void
    {
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');

        $this->makeIncident($flight, [$employee], now()->subDays(20));
        $this->makeIncident($flight, [$employee], now()->subDays(10));
        $third = $this->makeIncident($flight, [$employee], now()->subDays(2));
        $fourth = $this->makeIncident($flight, [$employee], now()->subDay());

        $service = app(IncidentAnalysisService::class);
        $service->analyze($third);
        $service->analyze($fourth);

        $this->assertSame(1, $employee->periodiRizika()->whereNull('datum_kraja')->count());
    }

    public function test_recording_an_incident_through_the_service_flags_a_repeat_offender(): void
    {
        $flight = $this->makeFlight();
        $type = IncidentType::factory()->create();
        $severity = SeverityLevel::factory()->create();
        $employee = $this->makeEmployee('pilot');

        $this->makeIncident($flight, [$employee], now()->subDays(15));
        $this->makeIncident($flight, [$employee], now()->subDays(5));

        app(IncidentService::class)->record([
            'flight_id' => $flight->id,
            'incident_type_id' => $type->id,
            'severity_level_id' => $severity->id,
            'occurred_at' => now(),
            'description' => 'Treći incident u mesecu.',
        ], [$employee->user_id]);

        $this->assertSame(1, $employee->periodiRizika()->whereNull('datum_kraja')->count());
    }
}
