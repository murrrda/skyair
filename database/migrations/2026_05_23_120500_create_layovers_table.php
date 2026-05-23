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
        Schema::create('layovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes');
            // FK constraint to airports added in a follow-up migration once the airports table exists.
            $table->foreignId('airport_id');
            $table->unsignedSmallInteger('stop_order');
            $table->unsignedInteger('expected_stay');
            $table->timestamps();

            $table->unique(['route_id', 'stop_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layovers');
    }
};
