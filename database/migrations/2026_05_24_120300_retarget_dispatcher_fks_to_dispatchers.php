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
        foreach (['flights', 'route_changes', 'plane_changes'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['dispatcher_id']);
                $t->foreign('dispatcher_id')
                    ->references('user_id')
                    ->on('dispatchers');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['flights', 'route_changes', 'plane_changes'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['dispatcher_id']);
                $t->foreign('dispatcher_id')
                    ->references('user_id')
                    ->on('zaposleni');
            });
        }
    }
};
