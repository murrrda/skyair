<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_assignment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_ticket_id');
            $table->unsignedBigInteger('assigned_employee_id')->nullable();
            $table->jsonb('candidate_scores')->nullable();
            $table->string('outcome');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('support_ticket_id')->references('id')->on('support_ticket')->cascadeOnDelete();
            $table->foreign('assigned_employee_id')->references('user_id')->on('zaposleni')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_assignment_logs');
    }
};
