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
            // Stable base price derived once from the route distance.
            $table->decimal('base_price', 10, 2)->nullable();
            // Dynamic price after occupancy & season adjustments, refreshed
            // periodically by the flights:recompute-prices command.
            $table->decimal('current_price', 10, 2)->nullable();
            // When current_price was last recomputed (used to throttle updates).
            $table->timestamp('price_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'current_price', 'price_updated_at']);
        });
    }
};
