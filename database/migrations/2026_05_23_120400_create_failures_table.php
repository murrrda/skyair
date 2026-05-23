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
        Schema::create('failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plane_id')->constrained('planes');
            $table->foreignId('flight_id')->nullable()->constrained('flights');
            $table->dateTime('report_time');
            $table->text('description');
            $table->unsignedTinyInteger('seriousness_level');
            $table->enum('status', ['fixed', 'currently_serviced', 'waiting_for_service'])->default('waiting_for_service');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failures');
    }
};
