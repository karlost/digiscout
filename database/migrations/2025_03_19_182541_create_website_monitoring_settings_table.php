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
        Schema::create('website_monitoring_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_tool_id')->constrained()->cascadeOnDelete();
            $table->integer('interval');
            $table->boolean('enabled')->default(true);
            $table->float('threshold')->nullable();
            $table->boolean('notify')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_monitoring_settings');
    }
};
