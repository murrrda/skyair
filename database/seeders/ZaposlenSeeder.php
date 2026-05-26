<?php

namespace Database\Seeders;

use App\Models\TipUgovora;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZaposlenSeeder extends Seeder
{
    /**
     * Wipe existing users/employees/contracts and seed a fresh set of
     * fully populated employees. All seeded users share the password
     * "password" and use ASCII-only emails.
     */
    public function run(): void
    {
        $this->call(TipUgovoraSeeder::class);

        $tipovi = TipUgovora::pluck('id', 'naziv');
        $neodredjeno = $tipovi['Ugovor o radu na neodređeno'];
        $odredjeno = $tipovi['Ugovor o radu na određeno'];
        $sezonski = $tipovi['Sezonski ugovor o radu'];

        // [first, last, gender, role, country, tip_ugovora_id, datum_zaposlenja, datum_isteka, date_of_birth]
        $employees = [
            ['Marko', 'Markovic', 'male', 'admin', 'Srbija', $neodredjeno, '2019-02-01', null, '1985-04-12'],
            ['Petar', 'Petrovic', 'male', 'pilot', 'Srbija', $neodredjeno, '2020-06-15', null, '1988-09-23'],
            ['Nikola', 'Nikolic', 'male', 'pilot', 'Bosna i Hercegovina', $neodredjeno, '2021-03-01', null, '1990-01-30'],
            ['Jovan', 'Jovanovic', 'male', 'co_pilot', 'Crna Gora', $odredjeno, '2022-09-10', '2027-09-10', '1992-07-05'],
            ['Milan', 'Milanovic', 'male', 'dispatcher', 'Srbija', $neodredjeno, '2020-11-20', null, '1987-12-18'],
            ['Ana', 'Anic', 'female', 'agent', 'Srbija', $odredjeno, '2023-01-05', '2026-12-31', '1995-03-14'],
            ['Jelena', 'Jelic', 'female', 'agent', 'Hrvatska', $neodredjeno, '2021-08-08', null, '1991-06-27'],
            ['Ivana', 'Ivic', 'female', 'cabin_crew', 'Srbija', $neodredjeno, '2022-04-19', null, '1996-02-09'],
            ['Stefan', 'Stefanovic', 'male', 'cabin_crew', 'Bosna i Hercegovina', $sezonski, '2024-05-01', '2026-10-31', '1998-11-22'],
            ['Milica', 'Milic', 'female', 'cabin_crew', 'Crna Gora', $odredjeno, '2023-07-12', '2027-06-30', '1994-08-03'],
        ];

        DB::transaction(function () use ($employees) {
            DB::table('ugovori')->delete();
            DB::table('periodi_rizika')->delete();
            DB::table('zaposleni')->delete();
            DB::table('users')->delete();

            foreach ($employees as $index => [$first, $last, $gender, $role, $country, $tipId, $datumZaposlenja, $datumIsteka, $dob]) {
                $n = $index + 1;

                $email = $role === 'admin'
                    ? 'admin@skyair.com'
                    : strtolower($first.'.'.$last).'@skyair.com';

                $user = User::factory()->create([
                    'name' => $first.' '.$last,
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'date_of_birth' => $dob,
                    'jmbg' => str_pad((string) (1000000000000 + $n), 13, '0', STR_PAD_LEFT),
                    'phone_number' => '+381 60 '.(1000000 + $n),
                    'address' => 'Ulica '.$n.', Beograd',
                    'gender' => $gender,
                    'country' => $country,
                ]);

                $zaposlen = Zaposlen::create([
                    'user_id' => $user->id,
                    'role' => $role,
                    'datum_zaposlenja' => $datumZaposlenja,
                    'status' => 'aktivan',
                ]);

                $zaposlen->tipoviUgovora()->attach($tipId, [
                    'datum_potpisivanja' => $datumZaposlenja,
                    'datum_isteka' => $datumIsteka,
                    'napomena' => null,
                    'is_active' => true,
                ]);
            }
        });
    }
}
