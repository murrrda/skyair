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
        Schema::create('extraction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_ticket')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('category')->cascadeOnDelete();
            $table->text('description');
            $table->jsonb('extracted_fields');
            $table->text('raw_response')->nullable();
            $table->string('model_used');
            $table->float('confidence_threshold');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_logs');
    }
};
