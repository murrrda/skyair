<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uloge', function (Blueprint $table) {
            $table->id();

            // Codebook of crew roles on a flight. `code` mirrors the
            // Zaposlen.role values so eligibility can be matched.
            $table->string('code')->unique();
            $table->string('naziv');
            $table->text('opis')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uloge');
    }
};
