<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds historical sample data (flights, services, plane/route changes) spread
 * over the last ~6 months so the fleet-statistics dashboard has meaningful data.
 *
 * All seeded rows are tagged so they can be removed on rollback:
 *  - flights:       number starts with "FLH-"
 *  - services:      description contains "[seed-stats]"
 *  - plane/route changes: reason contains "[seed-stats]"
 */
return new class extends Migration
{
    private const MARKER = '[seed-stats]';

    private const FLIGHT_PREFIX = 'FLH-';

    public function up(): void
    {
        // Deterministic output across runs.
        mt_srand(20260628);

        $adminId = DB::table('zaposleni')->where('role', 'admin')->value('user_id')
            ?? DB::table('zaposleni')->value('user_id');
        $dispatcherId = DB::table('zaposleni')->where('role', 'dispatcher')->value('user_id') ?? $adminId;

        $planes = DB::table('planes')->orderBy('id')->get(['id']);
        $routes = DB::table('routes')->where('active', true)->get(['id', 'estimated_time']);

        // Nothing meaningful to seed against (e.g. a fresh test database).
        if ($adminId === null || $dispatcherId === null || $planes->isEmpty() || $routes->isEmpty()) {
            return;
        }

        $end = Carbon::now();
        $start = $end->copy()->subMonths(6)->startOfMonth();
        $totalDays = max(1, $start->diffInDays($end));

        $this->seedFlights($planes, $routes, $dispatcherId, $start, $totalDays);
        $this->seedServices($planes, $adminId, $start, $totalDays);
        $this->seedChanges($planes, $routes, $dispatcherId, $start, $totalDays);
    }

    public function down(): void
    {
        $flightIds = DB::table('flights')
            ->where('number', 'like', self::FLIGHT_PREFIX.'%')
            ->pluck('id');

        if ($flightIds->isNotEmpty()) {
            DB::table('plane_changes')->whereIn('flight_id', $flightIds)->delete();
            DB::table('route_changes')->whereIn('flight_id', $flightIds)->delete();
        }

        DB::table('plane_changes')->where('reason', 'like', '%'.self::MARKER.'%')->delete();
        DB::table('route_changes')->where('reason', 'like', '%'.self::MARKER.'%')->delete();
        DB::table('services')->where('description', 'like', '%'.self::MARKER.'%')->delete();
        DB::table('flights')->where('number', 'like', self::FLIGHT_PREFIX.'%')->delete();
    }

    private function seedFlights($planes, $routes, int $dispatcherId, Carbon $start, int $totalDays): void
    {
        // Varied daily flight intensity per aircraft so utilization differs across the fleet.
        $intensities = [1.2, 0.5, 0.9, 0.7];
        $rows = [];
        $sequence = 1;
        $now = Carbon::now();

        foreach ($planes as $index => $plane) {
            $intensity = $intensities[$index % count($intensities)];
            $count = (int) round($intensity * $totalDays);

            for ($i = 0; $i < $count; $i++) {
                $route = $routes[mt_rand(0, $routes->count() - 1)];
                $takeoff = $start->copy()
                    ->addDays(mt_rand(0, $totalDays))
                    ->setTime(mt_rand(5, 21), [0, 15, 30, 45][mt_rand(0, 3)]);

                if ($takeoff->greaterThan($now)) {
                    continue;
                }

                $arrival = $takeoff->copy()->addMinutes((int) $route->estimated_time);

                $rows[] = [
                    'number' => self::FLIGHT_PREFIX.str_pad((string) $sequence++, 6, '0', STR_PAD_LEFT),
                    'plane_id' => $plane->id,
                    'route_id' => $route->id,
                    'dispatcher_id' => $dispatcherId,
                    'expected_takeoff' => $takeoff,
                    'expected_arrival' => $arrival,
                    'status' => 'landed',
                    'crew_status' => 'staffed',
                    'longitude' => null,
                    'latitude' => null,
                    'created_at' => $takeoff,
                    'updated_at' => $arrival,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('flights')->insert($chunk);
        }
    }

    private function seedServices($planes, int $adminId, Carbon $start, int $totalDays): void
    {
        $centers = ['Lufthansa Technik', 'SR Technics', 'JAT Tehnika', 'Air France Industries', 'ST Engineering'];
        $rows = [];

        foreach ($planes as $plane) {
            $serviceCount = mt_rand(4, 6);

            // Spread services roughly evenly across the period, with some jitter.
            $segment = intdiv($totalDays, $serviceCount + 1);

            for ($i = 1; $i <= $serviceCount; $i++) {
                $dayOffset = min($totalDays, $segment * $i + mt_rand(-5, 5));
                $started = $start->copy()->addDays(max(0, $dayOffset))->setTime(mt_rand(7, 16), 0);
                $ended = $started->copy()->addHours(mt_rand(6, 72));

                $rows[] = [
                    'plane_id' => $plane->id,
                    'admin_id' => $adminId,
                    'started' => $started,
                    'ended' => $ended,
                    'status' => 'finished',
                    'description' => 'Redovan servis '.self::MARKER,
                    'price' => mt_rand(1500, 9000) * 10,
                    'service_center' => $centers[mt_rand(0, count($centers) - 1)],
                    'created_at' => $started,
                    'updated_at' => $ended,
                ];
            }
        }

        DB::table('services')->insert($rows);
    }

    private function seedChanges($planes, $routes, int $dispatcherId, Carbon $start, int $totalDays): void
    {
        $planeIds = $planes->pluck('id')->all();
        $routeIds = $routes->pluck('id')->all();

        // Attach changes to the historical flights we just seeded.
        $flights = DB::table('flights')
            ->where('number', 'like', self::FLIGHT_PREFIX.'%')
            ->inRandomOrder()
            ->limit(120)
            ->get(['id', 'plane_id', 'route_id']);

        if ($flights->isEmpty()) {
            return;
        }

        $planeReasons = ['Tehnički kvar aviona', 'Redovno održavanje', 'Operativna preraspodela flote'];
        $routeReasons = ['Loši vremenski uslovi', 'Zatvoren vazdušni prostor', 'Operativna optimizacija rute'];

        $planeChangeRows = [];
        $routeChangeRows = [];

        foreach ($flights as $i => $flight) {
            $requestedAt = $start->copy()->addDays(mt_rand(0, $totalDays))->setTime(mt_rand(6, 20), 0);
            $appliedAt = $requestedAt->copy()->addHours(mt_rand(1, 12));

            // ~40% aircraft changes, ~60% route changes.
            if ($i % 5 < 2 && count($planeIds) > 1) {
                $newPlane = $flight->plane_id;
                while ($newPlane === $flight->plane_id) {
                    $newPlane = $planeIds[mt_rand(0, count($planeIds) - 1)];
                }

                $planeChangeRows[] = [
                    'flight_id' => $flight->id,
                    'original_plane_id' => $flight->plane_id,
                    'new_plane_id' => $newPlane,
                    'dispatcher_id' => $dispatcherId,
                    'requested_at' => $requestedAt,
                    'applied_at' => $appliedAt,
                    'status' => 'applied',
                    'reason' => $planeReasons[mt_rand(0, count($planeReasons) - 1)].' '.self::MARKER,
                    'created_at' => $requestedAt,
                    'updated_at' => $appliedAt,
                ];
            } elseif (count($routeIds) > 1) {
                $newRoute = $flight->route_id;
                while ($newRoute === $flight->route_id) {
                    $newRoute = $routeIds[mt_rand(0, count($routeIds) - 1)];
                }

                $routeChangeRows[] = [
                    'flight_id' => $flight->id,
                    'original_route_id' => $flight->route_id,
                    'new_route_id' => $newRoute,
                    'dispatcher_id' => $dispatcherId,
                    'requested_at' => $requestedAt,
                    'applied_at' => $appliedAt,
                    'status' => 'applied',
                    'reason' => $routeReasons[mt_rand(0, count($routeReasons) - 1)].' '.self::MARKER,
                    'created_at' => $requestedAt,
                    'updated_at' => $appliedAt,
                ];
            }
        }

        if ($planeChangeRows !== []) {
            DB::table('plane_changes')->insert($planeChangeRows);
        }

        if ($routeChangeRows !== []) {
            DB::table('route_changes')->insert($routeChangeRows);
        }
    }
};
