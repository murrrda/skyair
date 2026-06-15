<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->foreign('employee_id')->references('user_id')->on('zaposleni')->cascadeOnDelete();
            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shifts');
    }
};
