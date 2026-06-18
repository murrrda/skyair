<?php

namespace Database\Seeders;

use App\Models\Flight;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\SeverityLevel;
use App\Models\Zaposlen;
use App\Services\IncidentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        // Codebooks the incidents depend on (idempotent).
        $this->call(IncidentTypeSeeder::class);
        $this->call(SeverityLevelSeeder::class);

        // Incidents need flights to attach to.
        if (Flight::doesntExist()) {
            $this->call(FlightSeeder::class);
        }

        // Don't pile up duplicates on re-run.
        if (Incident::exists()) {
            $this->command?->warn('IncidentSeeder: incidents already present, skipping.');

            return;
        }

        $flights = Flight::orderBy('id')->get();
        if ($flights->isEmpty()) {
            $this->command?->warn('IncidentSeeder: no flights available, skipping.');

            return;
        }

        $types = IncidentType::pluck('id', 'name');
        $severities = SeverityLevel::pluck('id', 'name');

        // Active crew by role (these can be marked responsible for an incident).
        $pilots = Zaposlen::where('status', 'aktivan')->where('role', 'pilot')->pluck('user_id')->values();
        $coPilots = Zaposlen::where('status', 'aktivan')->where('role', 'co_pilot')->pluck('user_id')->values();
        $cabin = Zaposlen::where('status', 'aktivan')->where('role', 'cabin_crew')->pluck('user_id')->values();

        // Pick the "repeat offenders" we want flagged as risky (threshold = 3 / 30 days).
        $riskyPilot = $pilots->first();          // will be on 3 incidents within the window
        $riskyCabin = $cabin->first();           // will be on 4 incidents within the window
        $otherCabin = $cabin->get(1);            // stays healthy (1 incident)
        $coPilot = $coPilots->first();

        // [days ago, type, severity, description, responsible employee ids]
        $rows = [
            [28, 'Tehnički kvar', 'Medium', 'Otkazao jedan od sistema za navigaciju tokom leta; posada prešla na rezervni sistem.', array_filter([$riskyPilot, $coPilot])],
            [25, 'Greška posade', 'Low', 'Propust u kontrolnoj listi pre poletanja, blagovremeno korigovano.', array_filter([$riskyPilot])],
            [21, 'Vremenski uslovi', 'Medium', 'Jaka turbulencija usled nepovoljnih vremenskih uslova, bez povređenih.', array_filter([$riskyCabin])],
            [18, 'Narušavanje bezbednosti', 'High', 'Putnik narušio bezbednosne procedure u kabini; intervenisala kabinska posada.', array_filter([$riskyCabin, $otherCabin])],
            [14, 'Greška posade', 'High', 'Pogrešno uneti podaci o gorivu, otkriveno pre poletanja.', array_filter([$riskyPilot])],
            [11, 'Medicinski incident', 'Medium', 'Zdravstveni problem putnika tokom leta, pružena prva pomoć.', array_filter([$riskyCabin])],
            [8, 'Tehnički kvar', 'Critical', 'Kvar na hidrauličnom sistemu, izvršeno prinudno sletanje na alternativni aerodrom.', array_filter([$coPilot])],
            [6, 'Narušavanje bezbednosti', 'Medium', 'Neovlašćeni pristup zoni kabine tokom leta.', array_filter([$riskyCabin])],
            [4, 'Prinudno sletanje', 'High', 'Prinudno sletanje usled dojave o tehničkoj neispravnosti.', array_filter([$coPilot])],
            [2, 'Vremenski uslovi', 'Low', 'Kašnjenje i preusmeravanje leta zbog nevremena na destinaciji.', []],
            [40, 'Medicinski incident', 'Low', 'Lakša povreda člana posade tokom turbulencije (van prozora analize).', array_filter([$otherCabin])],
            [35, 'Tehnički kvar', 'Medium', 'Problem sa klimatizacijom kabine, otklonjen po sletanju (van prozora analize).', []],
        ];

        $service = app(IncidentService::class);
        $flightCount = $flights->count();

        foreach ($rows as $i => [$daysAgo, $typeName, $severityName, $description, $responsible]) {
            $flight = $flights[$i % $flightCount];

            $service->record([
                'flight_id' => $flight->id,
                'incident_type_id' => $types[$typeName],
                'severity_level_id' => $severities[$severityName],
                'occurred_at' => Carbon::now()->subDays($daysAgo)->setTime(rand(7, 21), rand(0, 59)),
                'description' => $description,
            ], array_values($responsible));
        }

        $this->command?->info('IncidentSeeder: created '.count($rows).' incidents (analysis triggered for responsible crew).');
    }
}
