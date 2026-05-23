<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE support_ticket_number_seq START 0 MINVALUE 0');

        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('category_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('category')->onDelete('cascade');
            $table->string('field_name');
            $table->string('field_type')->default('text');
            $table->boolean('required')->default(true);
            $table->timestamps();
        });

        Schema::create('support_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('category')->onDelete('restrict');
            $table->text('description');
            $table->string('number')->unique();
            $table->enum('status', ['open', 'in_progress', 'requires_info', 'transferred', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('support_ticket_field_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_ticket')->onDelete('cascade');
            $table->foreignId('category_field_id')->constrained('category_field')->onDelete('cascade');
            $table->text('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_field_value');
        Schema::dropIfExists('support_ticket');
        Schema::dropIfExists('category_field');
        Schema::dropIfExists('category');
        DB::statement('DROP SEQUENCE IF EXISTS support_ticket_number_seq');
    }
};