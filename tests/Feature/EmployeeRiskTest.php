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
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeRiskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'incidents.analysis.window_days' => 30,
            'incidents.analysis.threshold' => 3,
            'incidents.analysis.pause_days' => 30,
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
            'severity_level_id' => SeverityLevel::factory()->create(['rank' => 3])->id,
            'occurred_at' => $occurredAt,
            'description' => 'Test incident.',
        ]);
        $incident->responsibleEmployees()->sync(collect($employees)->pluck('user_id')->all());

        return $incident;
    }

    private function flagEmployee(Flight $flight, Zaposlen $employee): void
    {
        $this->makeIncident($flight, [$employee], now()->subDays(20));
        $this->makeIncident($flight, [$employee], now()->subDays(10));
        $third = $this->makeIncident($flight, [$employee], now()->subDay());

        app(IncidentAnalysisService::class)->analyze($third);
    }

    public function test_flagged_employee_can_view_their_risk_overview(): void
    {
        $this->withoutVite();
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');
        $this->flagEmployee($flight, $employee);

        $this->actingAs($employee->user)
            ->get('/employee/risk')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employee/risk', false)
                ->where('employee.user_id', $employee->user_id)
                ->where('stats.recent_count', 3)
                ->where('pause.duration_days', 30)
                ->has('incidents', 3)
            );
    }

    public function test_employee_without_active_pause_gets_404(): void
    {
        $employee = $this->makeEmployee('pilot');

        $this->actingAs($employee->user)
            ->get('/employee/risk')
            ->assertNotFound();
    }

    public function test_risk_alert_is_shared_for_a_flagged_employee(): void
    {
        $this->withoutVite();
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');
        $this->flagEmployee($flight, $employee);

        $this->actingAs($employee->user)
            ->get('/employee/my-flights')
            ->assertInertia(fn ($page) => $page->where('riskAlert.url', '/employee/risk'));
    }

    public function test_no_risk_alert_for_a_healthy_employee(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee('pilot');

        $this->actingAs($employee->user)
            ->get('/employee/my-flights')
            ->assertInertia(fn ($page) => $page->where('riskAlert', null));
    }
}
