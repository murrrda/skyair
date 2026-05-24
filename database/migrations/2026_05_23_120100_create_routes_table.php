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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            // FK constraints to airports added in a follow-up migration once the airports table exists.
            $table->foreignId('starting_airport_id');
            $table->foreignId('landing_airport_id');
            $table->foreignId('admin_id')->constrained('zaposleni', 'user_id');
            $table->string('name');
            $table->unsignedInteger('distance_km');
            $table->unsignedInteger('estimated_time');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
