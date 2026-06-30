<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\Plane;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\Route;
use App\Models\TicketClass;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private int $seat = 0;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);

        return $user;
    }

    private function makeStaff(): User
    {
        $user = User::factory()->create();
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'dispatcher',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);
        DB::table('dispatchers')->insert(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);

        return $user;
    }

    private function makeFlight(User $staff, Carbon $takeoff, int $capacity, ?Route $route = null): Flight
    {
        $this->seq++;

        $plane = Plane::create([
            'reg_number' => 50000 + $this->seq,
            'admin_id' => $staff->id,
            'model' => 'Test Plane',
            'capacity' => $capacity,
            'luxury_level' => 2,
            'range_km' => 5000,
            'max_speed' => 800,
            'repair_service_interval' => 500,
            'model_year' => 2022,
            'status' => 'in_garage',
        ]);

        $route ??= $this->makeRoute($staff);

        return Flight::create([
            'plane_id' => $plane->id,
            'route_id' => $route->id,
            'dispatcher_id' => $staff->id,
            'expected_takeoff' => $takeoff,
            'expected_arrival' => $takeoff->copy()->addHours(2),
            'status' => 'scheduled',
        ]);
    }

    private function makeRoute(User $staff): Route
    {
        $this->seq++;

        $from = Airport::create(['iata_code' => 'F'.$this->seq, 'name' => 'From', 'city' => 'From', 'country' => 'X', 'season_type' => 'none']);
        $to = Airport::create(['iata_code' => 'T'.$this->seq, 'name' => 'To', 'city' => 'To', 'country' => 'Y', 'season_type' => 'none']);

        return Route::create([
            'starting_airport_id' => $from->id,
            'landing_airport_id' => $to->id,
            'admin_id' => $staff->id,
            'name' => 'Ruta '.$this->seq,
            'distance_km' => 500,
            'estimated_time' => 90,
            'active' => true,
        ]);
    }

    /**
     * Adds a flight on the given route at $date, anchored by one confirmed
     * reservation, plus $cancelled separate cancelled reservations.
     */
    private function cancellationsOnDate(User $staff, Route $route, TicketClass $class, User $customer, Carbon $date, int $cancelled): void
    {
        $flight = $this->makeFlight($staff, $date, 100, $route);
        $this->addReservation($flight, $class, $customer, 'confirmed', 1, 10000);

        for ($i = 0; $i < $cancelled; $i++) {
            $this->addReservation($flight, $class, $customer, 'cancelled', 1, 10000);
        }
    }

    private function addReservation(Flight $flight, TicketClass $class, User $customer, string $status, int $tickets, float $final): Reservation
    {
        $reservation = Reservation::create([
            'user_id' => $customer->id,
            'total_price' => $final * $tickets,
            'code' => 'SA-'.strtoupper(substr(uniqid(), -6)),
        ]);

        $state = ReservationState::create(['reservation_id' => $reservation->id, 'status' => $status]);
        $reservation->update(['latest_state_id' => $state->id]);

        for ($i = 0; $i < $tickets; $i++) {
            FlightTicket::create([
                'passenger_first_name' => 'Kup',
                'passenger_last_name' => 'Ac',
                'flight_id' => $flight->id,
                'reservation_id' => $reservation->id,
                'ticket_class_id' => $class->id,
                'base_price' => $final * 0.8,
                'final_price' => $final,
                'seat_number' => ++$this->seat,
            ]);
        }

        return $reservation;
    }

    /**
     * One known dataset: a class (Ekonomska), a full flight A with 3 confirmed
     * tickets + 1 cancelled reservation, and an empty flight B — both today.
     *
     * @return array{flight_a: Flight, flight_b: Flight, class: TicketClass}
     */
    private function seedScenario(): array
    {
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        $today = Carbon::now()->setTime(10, 0);
        $flightA = $this->makeFlight($staff, $today, 100);
        $flightB = $this->makeFlight($staff, $today, 100);

        $this->addReservation($flightA, $class, $customer, 'confirmed', 3, 10000);
        $this->addReservation($flightA, $class, $customer, 'cancelled', 1, 10000);

        return ['flight_a' => $flightA, 'flight_b' => $flightB, 'class' => $class];
    }

    private function makeCustomer(): User
    {
        return User::factory()->create();
    }

    private function range(int $back = 2, int $forward = 2): string
    {
        return 'date_from='.now()->subDays($back)->toDateString().'&date_to='.now()->addDays($forward)->toDateString();
    }

    public function test_admin_can_fetch_sales_analytics_for_valid_range(): void
    {
        $admin = $this->makeAdmin();
        $data = $this->seedScenario();

        $response = $this->actingAs($admin)->getJson('/admin/prodaja/analytics?'.$this->range());

        $response->assertOk()->assertJsonStructure([
            'kpis' => ['tickets_sold', 'revenue', 'cancellation_rate_pct', 'avg_occupancy_pct'],
            'occupancy_by_class' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
            'cancellation' => ['total_reservations', 'cancelled_reservations', 'rate_pct'],
            'cancellation_trend' => [['date', 'count']],
            'occupancy_extremes' => ['highest', 'lowest'],
        ]);

        $json = $response->json();

        // 3 non-cancelled tickets sold; the 4th (cancelled reservation) is excluded.
        $this->assertSame(3, $json['kpis']['tickets_sold']);
        $this->assertSame(30000.0, (float) $json['kpis']['revenue']);
        $this->assertSame(50.0, (float) $json['kpis']['cancellation_rate_pct']);
        $this->assertSame(1.5, (float) $json['kpis']['avg_occupancy_pct']);

        $this->assertSame(2, $json['cancellation']['total_reservations']);
        $this->assertSame(1, $json['cancellation']['cancelled_reservations']);
        $this->assertSame(50.0, (float) $json['cancellation']['rate_pct']);

        // Ekonomska: 3 sold of 140 total seats (200 capacity * 0.70 share).
        $eko = collect($json['occupancy_by_class'])->firstWhere('class_name', 'Ekonomska');
        $this->assertSame(3, $eko['sold']);
        $this->assertSame(140, $eko['total_seats']);

        // With default thresholds (≥85% / ≤30%) neither light flight is "high";
        // both are "low", emptiest first: B (0%) then A (3%).
        $this->assertSame([], $json['occupancy_extremes']['highest']);
        $this->assertSame($data['flight_b']->id, $json['occupancy_extremes']['lowest'][0]['flight_id']);
        $this->assertSame($data['flight_a']->id, $json['occupancy_extremes']['lowest'][1]['flight_id']);
        $this->assertSame(85.0, (float) $json['occupancy_extremes']['high_threshold']);
        $this->assertSame(30.0, (float) $json['occupancy_extremes']['low_threshold']);

        // The single cancellation lands on today's bucket of the zero-filled trend.
        $today = now()->toDateString();
        $todayRow = collect($json['cancellation_trend'])->firstWhere('date', $today);
        $this->assertSame(1, $todayRow['count']);
    }

    public function test_occupancy_by_class_is_split_into_calendar_seasons(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        // One flight per calendar season, each on a plane with capacity 100.
        $summer = $this->makeFlight($staff, Carbon::create(2026, 7, 15, 10), 100); // ljeto
        $winter = $this->makeFlight($staff, Carbon::create(2026, 1, 15, 10), 100); // zima
        $off = $this->makeFlight($staff, Carbon::create(2026, 4, 15, 10), 100);    // van sezone

        $this->addReservation($summer, $class, $customer, 'confirmed', 2, 10000);
        $this->addReservation($winter, $class, $customer, 'confirmed', 1, 10000);
        $this->addReservation($off, $class, $customer, 'confirmed', 3, 10000);

        $json = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk()
            ->assertJsonStructure([
                'occupancy_by_class_by_season' => [
                    'leto' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
                    'zima' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
                    'van_sezone' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
                ],
            ])
            ->json();

        $eko = fn (string $season) => collect($json['occupancy_by_class_by_season'][$season])
            ->firstWhere('class_name', 'Ekonomska');

        // Each season counts only its own flight; total seats = 100 capacity * 0.70 share.
        $this->assertSame(2, $eko('leto')['sold']);
        $this->assertSame(70, $eko('leto')['total_seats']);
        $this->assertSame(1, $eko('zima')['sold']);
        $this->assertSame(70, $eko('zima')['total_seats']);
        $this->assertSame(3, $eko('van_sezone')['sold']);
        $this->assertSame(70, $eko('van_sezone')['total_seats']);

        // The seasons partition the period: the unsplit breakdown is their sum.
        $ekoAll = collect($json['occupancy_by_class'])->firstWhere('class_name', 'Ekonomska');
        $this->assertSame(6, $ekoAll['sold']);
        $this->assertSame(210, $ekoAll['total_seats']);
    }

    public function test_cancellation_by_flight_breakdown(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        $flight = $this->makeFlight($staff, Carbon::create(2026, 6, 10, 10), 100);
        $this->addReservation($flight, $class, $customer, 'confirmed', 1, 10000);
        $this->addReservation($flight, $class, $customer, 'cancelled', 1, 10000);
        $this->addReservation($flight, $class, $customer, 'cancelled', 1, 10000);

        $json = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?date_from=2026-06-01&date_to=2026-06-30')
            ->assertOk()
            ->assertJsonStructure([
                'cancellation_by_flight' => [['flight_id', 'flight_number', 'route_name', 'cancelled', 'total', 'rate_pct']],
            ])
            ->json();

        // 1 confirmed + 2 cancelled reservations → 2 of 3 cancelled = 66.67%.
        $row = collect($json['cancellation_by_flight'])->firstWhere('flight_id', $flight->id);
        $this->assertSame(2, $row['cancelled']);
        $this->assertSame(3, $row['total']);
        $this->assertSame(66.67, $row['rate_pct']);
    }

    public function test_rising_cancellations_flags_only_upward_trending_routes(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        // Rising route: cancellations 0 → 1 → 3 across three weekly departures.
        $rising = $this->makeRoute($staff);
        $this->cancellationsOnDate($staff, $rising, $class, $customer, Carbon::create(2026, 6, 1, 10), 0);
        $this->cancellationsOnDate($staff, $rising, $class, $customer, Carbon::create(2026, 6, 8, 10), 1);
        $this->cancellationsOnDate($staff, $rising, $class, $customer, Carbon::create(2026, 6, 15, 10), 3);

        // Falling route: 3 → 1 → 0.
        $falling = $this->makeRoute($staff);
        $this->cancellationsOnDate($staff, $falling, $class, $customer, Carbon::create(2026, 6, 1, 10), 3);
        $this->cancellationsOnDate($staff, $falling, $class, $customer, Carbon::create(2026, 6, 8, 10), 1);
        $this->cancellationsOnDate($staff, $falling, $class, $customer, Carbon::create(2026, 6, 15, 10), 0);

        $json = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?date_from=2026-05-01&date_to=2026-07-01')
            ->assertOk()
            ->json();

        $flagged = array_column($json['rising_cancellations'], 'route_id');
        $this->assertContains($rising->id, $flagged);
        $this->assertNotContains($falling->id, $flagged);

        $risingRow = collect($json['rising_cancellations'])->firstWhere('route_id', $rising->id);
        $this->assertSame(4, $risingRow['total_cancelled']);
        $this->assertGreaterThan(0, $risingRow['slope']);
        $this->assertCount(3, $risingRow['points']);
    }

    public function test_occupancy_extremes_rank_by_configured_thresholds(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        $today = Carbon::now()->setTime(9, 0);
        $full = $this->makeFlight($staff, $today, 10);   // 9/10  = 90% → high
        $mid = $this->makeFlight($staff, $today, 10);    // 5/10  = 50% → neither
        $empty = $this->makeFlight($staff, $today, 10);  // 1/10  = 10% → low

        $this->addReservation($full, $class, $customer, 'confirmed', 9, 5000);
        $this->addReservation($mid, $class, $customer, 'confirmed', 5, 5000);
        $this->addReservation($empty, $class, $customer, 'confirmed', 1, 5000);

        $ext = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?'.$this->range())
            ->assertOk()
            ->assertJsonStructure([
                'occupancy_extremes' => [
                    'high_threshold',
                    'low_threshold',
                    'highest' => [['flight_id', 'flight_number', 'route_name', 'date', 'capacity', 'sold', 'occupancy_pct']],
                    'lowest' => [['flight_id', 'flight_number', 'route_name', 'date', 'capacity', 'sold', 'occupancy_pct']],
                ],
            ])
            ->json('occupancy_extremes');

        $highIds = array_column($ext['highest'], 'flight_id');
        $lowIds = array_column($ext['lowest'], 'flight_id');

        $this->assertContains($full->id, $highIds);     // 90% ≥ 85
        $this->assertNotContains($mid->id, $highIds);    // 50% is neither
        $this->assertNotContains($mid->id, $lowIds);
        $this->assertContains($empty->id, $lowIds);      // 10% ≤ 30

        // Each row carries the departure date and sold/capacity.
        $fullRow = collect($ext['highest'])->firstWhere('flight_id', $full->id);
        $this->assertSame(9, $fullRow['sold']);
        $this->assertSame(10, $fullRow['capacity']);
        $this->assertSame($today->toDateString(), $fullRow['date']);
    }

    public function test_empty_range_returns_zeroed_aggregates(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?date_from=2020-01-01&date_to=2020-01-07');

        $response->assertOk()
            ->assertJsonPath('kpis.tickets_sold', 0)
            ->assertJsonPath('kpis.revenue', 0)
            ->assertJsonPath('cancellation.total_reservations', 0)
            ->assertJsonPath('cancellation.rate_pct', 0)
            ->assertJsonPath('occupancy_extremes.highest', [])
            ->assertJsonPath('occupancy_extremes.lowest', []);

        // Zero-filled daily trend spans the whole 7-day range.
        $this->assertCount(7, $response->json('cancellation_trend'));
        $this->assertSame(0, $response->json('cancellation_trend.0.count'));
    }

    public function test_non_admin_receives_403(): void
    {
        $this->actingAs($this->makeCustomer())
            ->getJson('/admin/prodaja/analytics?'.$this->range())
            ->assertForbidden();
    }

    public function test_admin_can_download_pdf_report(): void
    {
        $admin = $this->makeAdmin();
        $this->seedScenario();

        $response = $this->actingAs($admin)
            ->get('/admin/prodaja/statistike/pdf?date_from='.now()->subDays(2)->toDateString().'&date_to='.now()->addDays(2)->toDateString());

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'prodaja-izvestaj-'.now()->addDays(2)->toDateString().'.pdf',
            $response->headers->get('Content-Disposition') ?? '',
        );
    }

    public function test_non_admin_cannot_download_pdf_report(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/admin/prodaja/statistike/pdf?'.$this->range())
            ->assertForbidden();
    }

    public function test_missing_date_params_returns_422(): void
    {
        $this->actingAs($this->makeAdmin())
            ->getJson('/admin/prodaja/analytics')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_from', 'date_to']);
    }

    public function test_date_to_before_date_from_returns_422(): void
    {
        $this->actingAs($this->makeAdmin())
            ->getJson('/admin/prodaja/analytics?date_from=2026-06-30&date_to=2026-06-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_admin_can_view_dashboard_page(): void
    {
        $admin = $this->makeAdmin();
        $this->seedScenario();

        $this->actingAs($admin)
            ->get('/admin/prodaja/statistike?'.$this->range())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/prodaja/statistike')
                ->has('analytics.kpis')
                ->has('analytics.occupancy_by_class')
                ->has('analytics.cancellation')
                ->has('analytics.cancellation_trend')
                ->has('analytics.occupancy_extremes')
                ->where('analytics.kpis.tickets_sold', 3)
                ->has('period.date_from')
                ->has('period.date_to')
            );
    }

    public function test_dashboard_defaults_to_last_30_days_without_params(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/prodaja/statistike')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/prodaja/statistike')
                ->where('period.date_from', now()->subDays(29)->toDateString())
                ->where('period.date_to', now()->toDateString())
            );
    }

    public function test_non_admin_cannot_view_dashboard_page(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/admin/prodaja/statistike?'.$this->range())
            ->assertForbidden();
    }
}
