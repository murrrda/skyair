<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->unsignedInteger('total_flight_hours')->default(0)->after('total_mileage');
            $table->unsignedInteger('hours_since_last_service')->default(0)->after('total_flight_hours');
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn(['total_flight_hours', 'hours_since_last_service']);
        });
    }
};
