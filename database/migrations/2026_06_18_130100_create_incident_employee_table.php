<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_employee', function (Blueprint $table) {
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->unsignedBigInteger('zaposlen_user_id');
            $table->foreign('zaposlen_user_id')->references('user_id')->on('zaposleni')->cascadeOnDelete();

            $table->primary(['incident_id', 'zaposlen_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_employee');
    }
};
