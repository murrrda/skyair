<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodi_rizika', function (Blueprint $table) {
            $table->id();
            $table->date('datum_pocetka');
            $table->date('datum_kraja')->nullable();
            $table->foreignId('razlog_id')->constrained('razlozi')->restrictOnDelete();
            $table->unsignedBigInteger('zaposlen_id');
            $table->foreign('zaposlen_id')->references('user_id')->on('zaposleni')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['zaposlen_id', 'datum_pocetka']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodi_rizika');
    }
};
