<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->foreignId('plane_id')->constrained('planes');
            $table->foreignId('route_id')->constrained('routes');
            $table->foreignId('dispatcher_id')->constrained('zaposleni', 'user_id');
            $table->dateTime('expected_takeoff');
            $table->dateTime('expected_arrival');
            $table->enum('status', [
                'scheduled',
                'boarding',
                'before_takeoff',
                'in_flight',
                'landed',
                'delayed',
                'cancelled',
            ])->default('scheduled');
            $table->decimal('longitude', 9, 6)->nullable();
            $table->decimal('latitude', 8, 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plane_id');
            $table->dropConstrainedForeignId('route_id');
            $table->dropConstrainedForeignId('dispatcher_id');
            $table->dropColumn([
                'expected_takeoff',
                'expected_arrival',
                'status',
                'longitude',
                'latitude',
            ]);
        });
    }
};
