<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Dodeljen;
use App\Models\EmployeeTraining;
use App\Models\Flight;
use App\Models\Plane;
use App\Models\Route;
use App\Models\TrainingType;
use App\Models\Uloga;
use App\Models\User;
use App\Models\Zaposlen;
use App\Services\CrewAssignmentService;
use Database\Seeders\UlogaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrewAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private CertificateType $certType;

    private TrainingType $trainingType;

    private Zaposlen $dispatcher;

    private Plane $plane;

    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UlogaSeeder::class);

        $this->certType = CertificateType::factory()->create();
        $this->trainingType = TrainingType::factory()->create();
        $this->dispatcher = $this->makeEmployee('dispatcher');
        DB::table('dispatchers')->insert([
            'user_id' => $this->dispatcher->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $beg = Airport::create(['iata_code' => 'BEG', 'name' => 'Beograd', 'city' => 'Beograd', 'country' => 'Srbija']);
        $cdg = Airport::create(['iata_code' => 'CDG', 'name' => 'Pariz', 'city' => 'Pariz', 'country' => 'Francuska']);

        $this->route = Route::create([
            'starting_airport_id' => $beg->id,
            'landing_airport_id' => $cdg->id,
            'admin_id' => $this->dispatcher->user_id,
            'name' => 'Beograd – Pariz',
            'distance_km' => 1860,
            'estimated_time' => 165,
        ]);

        $this->plane = Plane::create([
            'reg_number' => 99001,
            'admin_id' => $this->dispatcher->user_id,
            'model' => 'Airbus A320',
            'capacity' => 180,
            'luxury_level' => 2,
            'range_km' => 6000,
            'max_speed' => 830,
            'repair_service_interval' => 500,
            'model_year' => 2021,
        ]);
    }

    private function makeEmployee(string $role): Zaposlen
    {
        return Zaposlen::create([
            'user_id' => User::factory()->create()->id,
            'role' => $role,
            'datum_zaposlenja' => now()->subYear()->toDateString(),
            'status' => 'aktivan',
        ]);
    }

    private function qualify(Zaposlen $employee, bool $validCert = true, bool $hasTraining = true): Zaposlen
    {
        Certificate::create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $this->certType->id,
            'issued_at' => now()->subYear(),
            'expires_at' => $validCert ? now()->addYear() : now()->subMonth(),
        ]);

        if ($hasTraining) {
            EmployeeTraining::create([
                'zaposlen_user_id' => $employee->user_id,
                'training_type_id' => $this->trainingType->id,
                'started_at' => now()->subMonths(6),
                'finished_at' => now()->subMonths(5),
            ]);
        }

        return $employee;
    }

    private function makeFlight(string $takeoff = '2026-07-15 10:30', string $arrival = '2026-07-15 13:15'): Flight
    {
        return Flight::create([
            'plane_id' => $this->plane->id,
            'route_id' => $this->route->id,
            'dispatcher_id' => $this->dispatcher->user_id,
            'expected_takeoff' => $takeoff,
            'expected_arrival' => $arrival,
            'status' => 'scheduled',
        ]);
    }

    private function assign(Flight $flight): void
    {
        app(CrewAssignmentService::class)->assign($flight);
    }

    public function test_assigns_full_minimum_crew_when_qualified_staff_available(): void
    {
        $this->qualify($this->makeEmployee('pilot'));
        $this->qualify($this->makeEmployee('co_pilot'));
        $this->qualify($this->makeEmployee('cabin_crew'));
        $this->qualify($this->makeEmployee('cabin_crew'));

        $flight = $this->makeFlight();
        $this->assign($flight);

        $this->assertSame('staffed', $flight->fresh()->crew_status);
        $this->assertSame(4, $flight->crewAssignments()->count());
        $this->assertSame(1, $flight->crewAssignments()->whereRelation('uloga', 'code', 'pilot')->count());
        $this->assertSame(1, $flight->crewAssignments()->whereRelation('uloga', 'code', 'co_pilot')->count());
        $this->assertSame(2, $flight->crewAssignments()->whereRelation('uloga', 'code', 'cabin_crew')->count());
    }

    public function test_pilot_fills_the_co_pilot_seat_when_no_co_pilot_is_available(): void
    {
        $this->qualify($this->makeEmployee('pilot'));
        $this->qualify($this->makeEmployee('pilot'));
        $this->qualify($this->makeEmployee('cabin_crew'));
        $this->qualify($this->makeEmployee('cabin_crew'));

        $flight = $this->makeFlight();
        $this->assign($flight);

        $this->assertSame('staffed', $flight->fresh()->crew_status);

        $coPilot = $flight->crewAssignments()
            ->whereRelation('uloga', 'code', 'co_pilot')
            ->with('zaposlen')
            ->first();

        $this->assertNotNull($coPilot);
        $this->assertSame('pilot', $coPilot->zaposlen->role);
    }

    public function test_a_real_co_pilot_is_preferred_over_a_pilot_for_the_co_pilot_seat(): void
    {
        $pilot = $this->qualify($this->makeEmployee('pilot'));
        $coPilot = $this->qualify($this->makeEmployee('co_pilot'));
        $this->qualify($this->makeEmployee('cabin_crew'));
        $this->qualify($this->makeEmployee('cabin_crew'));

        $flight = $this->makeFlight();
        $this->assign($flight);

        $seat = $flight->crewAssignments()->whereRelation('uloga', 'code', 'co_pilot')->first();
        $this->assertSame($coPilot->user_id, $seat->zaposlen_user_id);
    }

    public function test_excludes_employee_with_expired_certificate(): void
    {
        $pilot = $this->qualify($this->makeEmployee('pilot'), validCert: false);

        $flight = $this->makeFlight();
        $this->assign($flight);

        $this->assertSame('understaffed', $flight->fresh()->crew_status);
        $this->assertDatabaseMissing('dodeljeni', [
            'flight_id' => $flight->id,
            'zaposlen_user_id' => $pilot->user_id,
        ]);
    }

    public function test_excludes_employee_without_completed_training(): void
    {
        $pilot = $this->qualify($this->makeEmployee('pilot'), hasTraining: false);

        $flight = $this->makeFlight();
        $this->assign($flight);

        $this->assertDatabaseMissing('dodeljeni', [
            'flight_id' => $flight->id,
            'zaposlen_user_id' => $pilot->user_id,
        ]);
    }

    public function test_excludes_employee_already_assigned_to_an_overlapping_flight(): void
    {
        $pilot = $this->qualify($this->makeEmployee('pilot'));

        // Existing assignment on an overlapping flight.
        $existing = $this->makeFlight('2026-07-15 10:00', '2026-07-15 12:00');
        Dodeljen::create([
            'flight_id' => $existing->id,
            'zaposlen_user_id' => $pilot->user_id,
            'uloga_id' => Uloga::where('code', 'pilot')->value('id'),
            'status' => 'confirmed',
        ]);

        $flight = $this->makeFlight('2026-07-15 11:00', '2026-07-15 13:00');
        $this->assign($flight);

        $this->assertDatabaseMissing('dodeljeni', [
            'flight_id' => $flight->id,
            'zaposlen_user_id' => $pilot->user_id,
        ]);
    }

    public function test_marks_flight_understaffed_when_no_crew_can_be_assigned(): void
    {
        $flight = $this->makeFlight();
        $this->assign($flight);

        $this->assertSame('understaffed', $flight->fresh()->crew_status);
        $this->assertSame(0, $flight->crewAssignments()->count());
    }

    public function test_my_flights_page_returns_the_employees_assignments(): void
    {
        $this->withoutVite();

        $pilot = $this->qualify($this->makeEmployee('pilot'));
        $flight = $this->makeFlight();
        $this->assign($flight);

        $this->actingAs($pilot->user)
            ->get('/employee/my-flights')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employee/my-flights', false)
                ->has('flights', 1)
                ->where('flights.0.route', 'Beograd → Pariz')
                ->where('flights.0.role', 'Kapetan')
                ->where('flights.0.status', 'confirmed')
            );
    }
}
