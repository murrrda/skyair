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
        Schema::create('flight_tickets', function(Blueprint $table) {
            $table->id();
            $table->string('passenger_first_name');
            $table->string('passenger_last_name');
            $table->foreignId('flight_id')->constrained();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_class_id')->constrained();
            $table->foreignId('baggage_id')->nullable()->unique()->constrained('baggages');
            $table->decimal('base_price', 10, 2);
            $table->decimal('final_price', 10, 2);
            $table->integer('seat_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
