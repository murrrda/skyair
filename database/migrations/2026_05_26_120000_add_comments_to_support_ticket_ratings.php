<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_ticket_rating', function (Blueprint $table) {
            $table->string('resolution_speed_comment', 500)->nullable()->after('resolution_speed');
            $table->string('communication_quality_comment', 500)->nullable()->after('communication_quality');
            $table->string('degree_of_resolution_comment', 500)->nullable()->after('degree_of_resolution');
        });

        Schema::table('support_ticket_rating_agent', function (Blueprint $table) {
            $table->string('comment', 500)->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('support_ticket_rating_agent', function (Blueprint $table) {
            $table->dropColumn('comment');
        });

        Schema::table('support_ticket_rating', function (Blueprint $table) {
            $table->dropColumn([
                'resolution_speed_comment',
                'communication_quality_comment',
                'degree_of_resolution_comment',
            ]);
        });
    }
};