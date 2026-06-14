<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS flights_number_seq START 1 MINVALUE 1');
        DB::statement('CREATE SEQUENCE IF NOT EXISTS baggages_number_seq START 1 MINVALUE 1');

        Schema::table('flights', function (Blueprint $table) {
            $table->string('number')->unique()->nullable()->after('id');
        });

        Schema::table('baggages', function (Blueprint $table) {
            $table->string('number')->unique()->nullable()->after('id');
        });

        // Backfill existing rows
        DB::statement("
            UPDATE flights
            SET number = 'FL-' || LPAD(nextval('flights_number_seq')::text, 5, '0')
            WHERE number IS NULL
        ");

        DB::statement("
            UPDATE baggages
            SET number = 'BG-' || LPAD(nextval('baggages_number_seq')::text, 5, '0')
            WHERE number IS NULL
        ");

        Schema::table('flights', function (Blueprint $table) {
            $table->string('number')->nullable(false)->change();
        });

        Schema::table('baggages', function (Blueprint $table) {
            $table->string('number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn('number');
        });

        Schema::table('baggages', function (Blueprint $table) {
            $table->dropColumn('number');
        });

        DB::statement('DROP SEQUENCE IF EXISTS flights_number_seq');
        DB::statement('DROP SEQUENCE IF EXISTS baggages_number_seq');
    }
};
