<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First drop if exists to avoid errors
        DB::statement('DROP VIEW IF EXISTS notifications_count_view');
        
        DB::statement("
            CREATE VIEW notifications_count_view AS
            SELECT 
                notifiable_id,
                notifiable_type,
                COUNT(*) as total_count,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_count
            FROM notifications
            GROUP BY notifiable_id, notifiable_type
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS notifications_count_view');
    }
};
