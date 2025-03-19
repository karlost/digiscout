<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Website extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'url',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the monitoring settings for the website.
     */
    public function monitoringSettings(): HasMany
    {
        return $this->hasMany(WebsiteMonitoringSetting::class);
    }

    /**
     * Get the monitoring results for the website.
     */
    public function monitoringResults(): HasMany
    {
        return $this->hasMany(MonitoringResult::class);
    }
    
    /**
     * Get the monitoring tools for the website through monitoring settings.
     * 
     * This relationship is used for CRUD form field and access only.
     * The actual relationship is managed through WebsiteMonitoringSetting model
     * and WebsiteObserver to handle all required fields.
     * 
     * Note: We explicitly don't use the standard sync() and attach() methods on this
     * relationship because our pivot table requires additional fields.
     */
    public function monitoring_tools()
    {
        return $this->belongsToMany(
            MonitoringTool::class, 
            'website_monitoring_settings', 
            'website_id', 
            'monitoring_tool_id'
        )
        ->withPivot(['interval', 'enabled', 'threshold', 'notify', 'notify_discord'])
        ->using(WebsiteMonitoringSetting::class);
    }
    
    /**
     * Override the attach method to ensure interval is always set
     */
    public function attachMonitoringTool($toolId, $interval = null)
    {
        // Find the tool to get its default interval
        $tool = MonitoringTool::find($toolId);
        
        if (!$tool) {
            return false;
        }
        
        // Use provided interval or default from tool, or fallback to 5
        $interval = $interval ?? $tool->default_interval ?? 5;
        
        // Create the monitoring setting with required fields
        return $this->monitoringSettings()->create([
            'monitoring_tool_id' => $toolId,
            'interval' => $interval,
            'enabled' => true,
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false,
        ]);
    }
    
    /**
     * Get the monitoring tool IDs for this website.
     * Helper method to use in forms and validation.
     */
    public function getMonitoringToolIdsAttribute()
    {
        return $this->monitoringSettings()->pluck('monitoring_tool_id')->toArray();
    }
    
    /**
     * Custom method to sync monitoring tools with required fields
     * This fixes the "SQLSTATE[HY000]: General error: 1364 Field 'interval' doesn't have a default value" error
     * 
     * @param array $toolIds IDs of the monitoring tools to sync
     * @param array $toolIntervals Optional array of tool intervals keyed by tool ID
     * @return $this
     */
    public function syncMonitoringTools(array $toolIds, array $toolIntervals = [])
    {
        // Get current tool IDs
        $currentToolIds = $this->monitoringSettings()
            ->pluck('monitoring_tool_id')
            ->toArray();
            
        // Find tools to add
        $toolsToAdd = array_diff($toolIds, $currentToolIds);
        
        // Find tools to remove
        $toolsToRemove = array_diff($currentToolIds, $toolIds);
        
        // Remove tools that are no longer selected
        if (!empty($toolsToRemove)) {
            $this->monitoringSettings()
                ->whereIn('monitoring_tool_id', $toolsToRemove)
                ->delete();
        }
        
        // Add new tools
        foreach ($toolsToAdd as $toolId) {
            // Get interval from the provided array or use default
            $interval = $toolIntervals[$toolId] ?? null;
            $this->attachMonitoringTool($toolId, $interval);
        }
        
        return $this;
    }
}