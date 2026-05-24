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
        Schema::create('plane_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id')->constrained('flights');
            $table->foreignId('original_plane_id')->constrained('planes');
            $table->foreignId('new_plane_id')->constrained('planes');
            $table->foreignId('dispatcher_id')->constrained('zaposleni', 'user_id');
            $table->dateTime('requested_at');
            $table->dateTime('applied_at')->nullable();
            $table->enum('status', ['requested', 'applied', 'rejected'])->default('requested');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plane_changes');
    }
};
