<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_ticket', function (Blueprint $table) {
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('status');
            $table->string('outcome')->nullable()->after('priority');
            $table->timestamp('closed_at')->nullable()->after('outcome');
        });

        Schema::create('support_ticket_work_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_ticket')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('user_id')->on('zaposleni')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('action')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['support_ticket_id', 'started_at']);
            $table->index(['employee_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_work_log');

        Schema::table('support_ticket', function (Blueprint $table) {
            $table->dropColumn(['priority', 'outcome', 'closed_at']);
        });
    }
};
