<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE support_ticket DROP CONSTRAINT IF EXISTS support_ticket_status_check');
        DB::statement("ALTER TABLE support_ticket ADD CONSTRAINT support_ticket_status_check CHECK (status::text = ANY (ARRAY['draft','open','in_progress','requires_info','transferred','closed']))");
    }

    public function down(): void
    {
        DB::statement("UPDATE support_ticket SET status = 'open' WHERE status = 'draft'");
        DB::statement('ALTER TABLE support_ticket DROP CONSTRAINT IF EXISTS support_ticket_status_check');
        DB::statement("ALTER TABLE support_ticket ADD CONSTRAINT support_ticket_status_check CHECK (status::text = ANY (ARRAY['open','in_progress','requires_info','transferred','closed']))");
    }
};
