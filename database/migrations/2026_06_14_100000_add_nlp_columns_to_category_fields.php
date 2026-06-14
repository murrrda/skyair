<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_field', function (Blueprint $table) {
            $table->string('display_label')->after('field_name');
            $table->string('prompt')->after('required');
            $table->string('validation_rule')->nullable()->after('prompt');
            $table->string('reference_table')->nullable()->after('validation_rule');
            $table->string('reference_column')->nullable()->after('reference_table');
        });
    }

    public function down(): void
    {
        Schema::table('category_field', function (Blueprint $table) {
            $table->dropColumn(['display_label', 'prompt', 'validation_rule', 'reference_table', 'reference_column']);
        });
    }
};
