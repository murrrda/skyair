<?php

namespace Tests\Feature;

use App\Models\TipUgovora;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DispatcherSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(string $role = 'pilot'): Zaposlen
    {
        $user = User::factory()->create([
            'first_name' => 'Marko',
            'last_name' => 'Markovic',
            'date_of_birth' => '1990-01-01',
            'jmbg' => '1234567890123',
        ]);

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

    /**
     * @return array<string, mixed>
     */
    private function editPayload(Zaposlen $employee, int $tipUgovoraId, string $role): array
    {
        return [
            'first_name' => $employee->user->first_name,
            'last_name' => $employee->user->last_name,
            'email' => $employee->user->email,
            'date_of_birth' => '1990-01-01',
            'jmbg' => '1234567890123',
            'role' => $role,
            'datum_zaposlenja' => now()->subYear()->toDateString(),
            'tip_ugovora_id' => $tipUgovoraId,
            'action' => 'save',
        ];
    }

    private function dispatcherRowExists(int $userId): bool
    {
        return DB::table('dispatchers')->where('user_id', $userId)->exists();
    }

    public function test_creating_a_dispatcher_employee_creates_the_dispatchers_row(): void
    {
        $tip = TipUgovora::create(['naziv' => 'Stalni', 'opis' => null]);

        $this->actingAs($this->admin())->post('/admin/employee', [
            'first_name' => 'Disp',
            'last_name' => 'Atcher',
            'email' => 'disp@skyair.test',
            'date_of_birth' => '1988-05-05',
            'address' => 'Beograd',
            'phone_number' => '+381601234567',
            'jmbg' => '9876543210123',
            'role' => 'dispatcher',
            'datum_zaposlenja' => now()->subMonth()->toDateString(),
            'status' => 'aktivan',
            'tip_ugovora_id' => $tip->id,
            'datum_potpisivanja' => now()->subMonth()->toDateString(),
            // datum_isteka intentionally omitted — store() must tolerate it.
        ])->assertRedirect();

        $user = User::where('email', 'disp@skyair.test')->firstOrFail();
        $this->assertTrue($this->dispatcherRowExists($user->id));
    }

    public function test_promoting_to_dispatcher_adds_the_row(): void
    {
        $employee = $this->makeEmployee('pilot');
        $tip = TipUgovora::create(['naziv' => 'Stalni', 'opis' => null]);

        $this->assertFalse($this->dispatcherRowExists($employee->user_id));

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}", $this->editPayload($employee, $tip->id, 'dispatcher'))
            ->assertRedirect();

        $this->assertTrue($this->dispatcherRowExists($employee->user_id));
    }

    public function test_demoting_from_dispatcher_removes_the_row(): void
    {
        $employee = $this->makeEmployee('dispatcher');
        DB::table('dispatchers')->insert([
            'user_id' => $employee->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tip = TipUgovora::create(['naziv' => 'Stalni', 'opis' => null]);

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}", $this->editPayload($employee, $tip->id, 'pilot'))
            ->assertRedirect();

        $this->assertFalse($this->dispatcherRowExists($employee->user_id));
    }
}
