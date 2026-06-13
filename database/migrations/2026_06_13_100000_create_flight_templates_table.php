<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatcher_id')->constrained('dispatchers', 'user_id');
            $table->string('name');
            $table->foreignId('route_id')->constrained();
            $table->time('departure_time');
            $table->integer('duration_minutes');
            $table->integer('min_capacity')->nullable();
            $table->integer('luxury_level')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_templates');
    }
};
