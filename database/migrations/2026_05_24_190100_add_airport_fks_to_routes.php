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
        Schema::table('routes', function (Blueprint $table) {
            $table->foreign('starting_airport_id')
                ->references('id')
                ->on('airports');
            $table->foreign('landing_airport_id')
                ->references('id')
                ->on('airports');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['starting_airport_id']);
            $table->dropForeign(['landing_airport_id']);
        });
    }
};
