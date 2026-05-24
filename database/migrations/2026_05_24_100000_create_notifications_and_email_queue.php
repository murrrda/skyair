<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_queue', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('body');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['sent_at', 'failed_at', 'attempts']);
        });

        Schema::create('support_ticket_change_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_ticket')->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['support_ticket_id', 'created_at']);
            $table->index(['field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_change_log');
        Schema::dropIfExists('email_queue');
        Schema::dropIfExists('notifications');
    }
};