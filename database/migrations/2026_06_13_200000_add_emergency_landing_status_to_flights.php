<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE flights DROP CONSTRAINT flights_status_check');
        DB::statement("ALTER TABLE flights ADD CONSTRAINT flights_status_check CHECK (status::text = ANY (ARRAY['scheduled','boarding','before_takeoff','in_flight','landed','delayed','cancelled','emergency_landing']::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE flights DROP CONSTRAINT flights_status_check');
        DB::statement("ALTER TABLE flights ADD CONSTRAINT flights_status_check CHECK (status::text = ANY (ARRAY['scheduled','boarding','before_takeoff','in_flight','landed','delayed','cancelled']::text[]))");
    }
};
