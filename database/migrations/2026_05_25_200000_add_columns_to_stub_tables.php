<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_classes', function (Blueprint $table) {
            $table->string('name');
            $table->decimal('multiplier', 3, 2)->default(1.00);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('card');
            $table->enum('status', ['pending', 'paid', 'refunded'])->default('pending');
        });

        Schema::table('reservation_states', function (Blueprint $table) {
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->foreignId('cancellation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_classes', function (Blueprint $table) {
            $table->dropColumn(['name', 'multiplier']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['amount', 'method', 'status']);
        });

        Schema::table('reservation_states', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
            $table->dropColumn('status');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
