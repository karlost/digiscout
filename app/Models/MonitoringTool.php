<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class MonitoringTool extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_interval',
        'interval_unit',
        'is_active',
        'is_default',
        'display_order',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'display_order' => 'integer',
    ];
    
    /**
     * Get the monitoring settings associated with this tool.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(WebsiteMonitoringSetting::class);
    }
    
    /**
     * Get the monitoring results for this tool.
     */
    public function results(): HasMany
    {
        return $this->hasMany(MonitoringResult::class);
    }

    /**
     * Get the website monitoring settings for this tool.
     */
    public function websiteMonitoringSettings(): HasMany
    {
        return $this->hasMany(WebsiteMonitoringSetting::class);
    }

    /**
     * Get the monitoring results for this tool.
     */
    public function monitoringResults(): HasMany
    {
        return $this->hasMany(MonitoringResult::class);
    }
}
