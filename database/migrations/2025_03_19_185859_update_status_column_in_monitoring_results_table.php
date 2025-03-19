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
        // First, backup existing data
        $results = DB::table('monitoring_results')->get();
        
        // Drop and recreate the column as string
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->string('status')->after('monitoring_tool_id');
        });
        
        // Restore data
        foreach ($results as $result) {
            DB::table('monitoring_results')
                ->where('id', $result->id)
                ->update(['status' => $result->status]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, backup existing data
        $results = DB::table('monitoring_results')->get();
        
        // Drop and recreate the column as enum
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->enum('status', ['success', 'warning', 'failure'])->after('monitoring_tool_id');
        });
        
        // Restore data
        foreach ($results as $result) {
            // If the status value is not one of the enum values, default to 'failure'
            $status = in_array($result->status, ['success', 'warning', 'failure']) 
                ? $result->status 
                : 'failure';
                
            DB::table('monitoring_results')
                ->where('id', $result->id)
                ->update(['status' => $status]);
        }
    }
};
