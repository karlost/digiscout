<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class WebsiteMonitoringSetting extends Pivot
{
    use CrudTrait, HasFactory;
    
    // Set the table explicitly
    protected $table = 'website_monitoring_settings';
    
    // Use timestamps (required for Pivot models)
    public $timestamps = true;
    
    // Allow mass assignment
    public $incrementing = true;
    
    protected $fillable = [
        'website_id',
        'monitoring_tool_id',
        'interval',
        'enabled',
        'threshold',
        'notify',
        'notify_discord',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'notify' => 'boolean',
        'notify_discord' => 'boolean',
        'threshold' => 'float',
    ];

    /**
     * Get the website that owns this monitoring setting.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Get the monitoring tool that owns this monitoring setting.
     */
    public function monitoringTool(): BelongsTo
    {
        return $this->belongsTo(MonitoringTool::class);
    }
}
