<?php

namespace App\Observers;

use App\Models\Website;

class WebsiteObserver
{
    /**
     * Handle the Website "created" event.
     */
    public function created(Website $website): void
    {
        // Retrieve monitoring tools data from the request
        $monitoringToolIds = request()->input('monitoring_tools', []);
        
        if (empty($monitoringToolIds)) {
            // If no tools were manually selected, use the default tools
            $monitoringToolIds = \App\Models\MonitoringTool::where('is_active', true)
                ->where('is_default', true)
                ->pluck('id')
                ->toArray();
        }
        
        // Get default intervals for each tool
        $toolIntervals = [];
        foreach ($monitoringToolIds as $toolId) {
            $tool = \App\Models\MonitoringTool::find($toolId);
            if ($tool) {
                $toolIntervals[$toolId] = $tool->default_interval ?? 5; // Fallback to 5 minutes
            }
        }
        
        // Use our custom sync method that handles the interval field
        $website->syncMonitoringTools($monitoringToolIds, $toolIntervals);
    }

    /**
     * Handle the Website "updated" event.
     */
    public function updated(Website $website): void
    {
        // If monitoring_tools input exists in the request, update settings
        if (request()->has('monitoring_tools')) {
            // Retrieve monitoring tools data from the request
            $monitoringToolIds = request()->input('monitoring_tools', []);
            
            // Get default intervals for each tool or preserve existing intervals
            $toolIntervals = [];
            
            // For existing tools, preserve their current intervals
            $existingSettings = $website->monitoringSettings()->get();
            foreach ($existingSettings as $setting) {
                if (in_array($setting->monitoring_tool_id, $monitoringToolIds)) {
                    $toolIntervals[$setting->monitoring_tool_id] = $setting->interval;
                }
            }
            
            // For new tools, get default intervals
            foreach ($monitoringToolIds as $toolId) {
                if (!isset($toolIntervals[$toolId])) {
                    $tool = \App\Models\MonitoringTool::find($toolId);
                    if ($tool) {
                        $toolIntervals[$toolId] = $tool->default_interval ?? 5; // Fallback to 5 minutes
                    }
                }
            }
            
            // Use our custom sync method that handles the interval field
            $website->syncMonitoringTools($monitoringToolIds, $toolIntervals);
        }
    }

    /**
     * Handle the Website "deleted" event.
     */
    public function deleted(Website $website): void
    {
        //
    }

    /**
     * Handle the Website "restored" event.
     */
    public function restored(Website $website): void
    {
        //
    }

    /**
     * Handle the Website "force deleted" event.
     */
    public function forceDeleted(Website $website): void
    {
        //
    }
}
