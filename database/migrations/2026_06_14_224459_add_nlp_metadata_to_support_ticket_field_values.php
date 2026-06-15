<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_ticket_field_value', function (Blueprint $table) {
            $table->float('confidence')->nullable()->after('value');
            $table->string('source', 20)->default('manual')->after('confidence');
        });
    }

    public function down(): void
    {
        Schema::table('support_ticket_field_value', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'source']);
        });
    }
};
