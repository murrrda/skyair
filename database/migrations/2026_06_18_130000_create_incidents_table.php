<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id')->constrained('flights')->cascadeOnDelete();
            $table->foreignId('incident_type_id')->constrained('incident_types')->restrictOnDelete();
            $table->foreignId('severity_level_id')->constrained('severity_levels')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();

            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
