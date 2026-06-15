<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE support_ticket DROP CONSTRAINT IF EXISTS support_ticket_status_check');
        DB::statement("ALTER TABLE support_ticket ADD CONSTRAINT support_ticket_status_check CHECK (status::text = ANY (ARRAY['draft','abandoned','open','in_progress','requires_info','transferred','closed']))");

        Schema::table('extraction_logs', function (Blueprint $table) {
            $table->jsonb('validation_results')->nullable()->after('confidence_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('extraction_logs', function (Blueprint $table) {
            $table->dropColumn('validation_results');
        });

        DB::statement("UPDATE support_ticket SET status = 'draft' WHERE status = 'abandoned'");
        DB::statement('ALTER TABLE support_ticket DROP CONSTRAINT IF EXISTS support_ticket_status_check');
        DB::statement("ALTER TABLE support_ticket ADD CONSTRAINT support_ticket_status_check CHECK (status::text = ANY (ARRAY['draft','open','in_progress','requires_info','transferred','closed']))");
    }
};
