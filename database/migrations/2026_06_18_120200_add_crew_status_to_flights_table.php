<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            // null until crew assignment has run; then 'staffed' or 'understaffed'.
            $table->string('crew_status')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn('crew_status');
        });
    }
};
