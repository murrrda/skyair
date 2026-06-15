<?php

namespace Database\Seeders;

use App\Models\EmployeeShift;
use App\Models\Zaposlen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmployeeShiftSeeder extends Seeder
{
    public function run(): void
    {
        $agents = Zaposlen::where('role', 'agent')->where('status', 'aktivan')->get();

        if ($agents->isEmpty()) {
            return;
        }

        $today = Carbon::today();

        foreach (range(0, 6) as $dayOffset) {
            $date = $today->copy()->addDays($dayOffset);

            $shifts = [
                ['start_time' => '00:00', 'end_time' => '08:00'],
                ['start_time' => '08:00', 'end_time' => '16:00'],
                ['start_time' => '14:00', 'end_time' => '22:00'],
            ];

            foreach ($agents->values() as $i => $agent) {
                $shift = $shifts[$i % count($shifts)];

                EmployeeShift::updateOrCreate(
                    ['employee_id' => $agent->user_id, 'date' => $date->toDateString()],
                    $shift,
                );
            }
        }
    }
}
