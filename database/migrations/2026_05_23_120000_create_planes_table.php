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
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->integer('reg_number')->unique();
            $table->foreignId('admin_id')->constrained('zaposleni', 'user_id');
            $table->string('model');
            $table->unsignedInteger('capacity');
            $table->unsignedTinyInteger('luxury_level');
            $table->unsignedInteger('range_km');
            $table->unsignedInteger('max_speed');
            $table->unsignedInteger('repair_service_interval');
            $table->unsignedSmallInteger('model_year');
            $table->enum('status', ['in_garage', 'in_flight', 'in_service'])->default('in_garage');
            $table->date('commissioned_at')->nullable();
            $table->unsignedBigInteger('total_mileage')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
