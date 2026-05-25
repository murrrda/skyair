<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_rating', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')
                ->unique()
                ->constrained('support_ticket')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('resolution_speed');
            $table->unsignedTinyInteger('communication_quality')->nullable();
            $table->unsignedTinyInteger('degree_of_resolution')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE support_ticket_rating ADD CONSTRAINT support_ticket_rating_resolution_speed_chk CHECK (resolution_speed BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE support_ticket_rating ADD CONSTRAINT support_ticket_rating_communication_quality_chk CHECK (communication_quality IS NULL OR communication_quality BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE support_ticket_rating ADD CONSTRAINT support_ticket_rating_degree_of_resolution_chk CHECK (degree_of_resolution IS NULL OR degree_of_resolution BETWEEN 1 AND 5)');

        Schema::create('support_ticket_rating_agent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_rating_id')
                ->constrained('support_ticket_rating')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('user_id')->on('zaposleni')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['support_ticket_rating_id', 'employee_id']);
        });

        DB::statement('ALTER TABLE support_ticket_rating_agent ADD CONSTRAINT support_ticket_rating_agent_rating_chk CHECK (rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_rating_agent');
        Schema::dropIfExists('support_ticket_rating');
    }
};
