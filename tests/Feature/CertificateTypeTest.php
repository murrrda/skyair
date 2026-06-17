<?php

namespace Tests\Feature;

use App\Models\CertificateType;
use Database\Seeders\CertificateTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_the_catalogue(): void
    {
        $this->seed(CertificateTypeSeeder::class);

        $this->assertSame(3, CertificateType::count());
        $this->assertDatabaseHas('certificate_types', ['name' => 'Pilotska licenca']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CertificateTypeSeeder::class);
        $this->seed(CertificateTypeSeeder::class);

        $this->assertSame(3, CertificateType::count());
    }

    public function test_active_scope_returns_only_active_types(): void
    {
        CertificateType::factory()->create(['is_active' => true]);
        CertificateType::factory()->create(['is_active' => false]);

        $this->assertSame(1, CertificateType::query()->active()->count());
    }
}
