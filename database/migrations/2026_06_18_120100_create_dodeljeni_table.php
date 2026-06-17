<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dodeljeni', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('flight_id');
            $table->unsignedBigInteger('zaposlen_user_id');
            $table->unsignedBigInteger('uloga_id');

            $table->foreign('flight_id')
                ->references('id')->on('flights')
                ->cascadeOnDelete();

            $table->foreign('zaposlen_user_id')
                ->references('user_id')->on('zaposleni')
                ->cascadeOnDelete();

            $table->foreign('uloga_id')
                ->references('id')->on('uloge')
                ->restrictOnDelete();

            $table->string('status')->default('confirmed');

            $table->timestamps();

            // An employee can hold at most one assignment per flight.
            $table->unique(['flight_id', 'zaposlen_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dodeljeni');
    }
};
