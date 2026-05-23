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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plane_id')->constrained('planes');
            $table->foreignId('admin_id')->constrained('zaposleni', 'user_id');
            $table->dateTime('started');
            $table->dateTime('ended')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'finished'])->default('pending');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('service_center');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
