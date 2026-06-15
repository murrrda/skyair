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
        Schema::table('airports', function (Blueprint $table) {
            // Marks a destination as a summer (beach) or winter (ski) hotspot,
            // or 'none' for a neutral destination. Drives the season pricing factor.
            $table->enum('season_type', ['summer', 'winter', 'none'])->default('none');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('season_type');
        });
    }
};
