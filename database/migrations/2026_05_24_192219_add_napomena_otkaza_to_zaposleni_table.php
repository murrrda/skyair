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
        Schema::table('zaposleni', function (Blueprint $table) {
            $table->text('napomena_otkaza')->nullable()->after('razlog_otkaza');
        });
    }

    public function down(): void
    {
        Schema::table('zaposleni', function (Blueprint $table) {
            $table->dropColumn('napomena_otkaza');
        });
    }
};
