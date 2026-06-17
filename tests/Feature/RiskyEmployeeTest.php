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

class RiskyEmployeeTest extends TestCase
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

    public function test_admin_sees_a_flagged_employee_in_the_risky_list(): void
    {
        $this->withoutVite();
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');
        $this->flagEmployee($flight, $employee);

        $this->actingAs($this->admin())
            ->get('/admin/incidenti/rizicni')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/incidenti/rizicni/index', false)
                ->where('meta.count', 1)
                ->where('employees.0.user_id', $employee->user_id)
                ->where('employees.0.incident_count', 3)
            );
    }

    public function test_risky_list_is_empty_when_no_one_is_flagged(): void
    {
        $this->withoutVite();

        $this->actingAs($this->admin())
            ->get('/admin/incidenti/rizicni')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('meta.count', 0));
    }

    public function test_non_admin_cannot_view_the_risky_list(): void
    {
        $this->actingAs($this->makeEmployee('pilot')->user)
            ->get('/admin/incidenti/rizicni')
            ->assertForbidden();
    }

    public function test_admin_can_view_a_risky_employee_details(): void
    {
        $this->withoutVite();
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');
        $this->flagEmployee($flight, $employee);

        $this->actingAs($this->admin())
            ->get("/admin/incidenti/rizicni/{$employee->user_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/incidenti/rizicni/show', false)
                ->where('employee.user_id', $employee->user_id)
                ->where('stats.recent_count', 3)
                ->where('stats.over_by', 0)
                ->where('pause.duration_days', 30)
                ->has('incidents', 3)
            );
    }

    public function test_details_404_for_an_employee_without_an_active_pause(): void
    {
        $employee = $this->makeEmployee('pilot');

        $this->actingAs($this->admin())
            ->get("/admin/incidenti/rizicni/{$employee->user_id}")
            ->assertNotFound();
    }

    public function test_non_admin_cannot_view_risky_employee_details(): void
    {
        $flight = $this->makeFlight();
        $employee = $this->makeEmployee('pilot');
        $this->flagEmployee($flight, $employee);

        $this->actingAs($this->makeEmployee('pilot')->user)
            ->get("/admin/incidenti/rizicni/{$employee->user_id}")
            ->assertForbidden();
    }
}
