<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ugovori', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        // Keep only the most recent contract per employee active.
        DB::statement('UPDATE ugovori SET is_active = false');
        DB::statement('
            UPDATE ugovori u SET is_active = true
            FROM (
                SELECT DISTINCT ON (zaposlen_user_id) zaposlen_user_id, tip_ugovora_id
                FROM ugovori
                ORDER BY zaposlen_user_id, created_at DESC, tip_ugovora_id DESC
            ) latest
            WHERE u.zaposlen_user_id = latest.zaposlen_user_id
              AND u.tip_ugovora_id = latest.tip_ugovora_id
        ');
    }

    public function down(): void
    {
        Schema::table('ugovori', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
