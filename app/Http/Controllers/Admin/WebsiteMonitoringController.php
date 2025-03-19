<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitoringTool;
use App\Models\Website;
use Illuminate\Http\Request;

class WebsiteMonitoringController extends Controller
{
    public function __construct()
    {
        // In tests, this middleware is mocked so we use web middleware
        $this->middleware(['web', config('backpack.base.middleware_key', 'admin')]);
    }
    
    /**
     * Show the monitoring tools configuration page for a website
     */
    public function configure(Request $request, $websiteId)
    {
        $website = Website::findOrFail($websiteId);
        
        // Get all active monitoring tools
        $monitoringTools = MonitoringTool::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
            
        // Get current website monitoring settings
        $currentSettings = $website->monitoringSettings()
            ->with('monitoringTool')
            ->get()
            ->keyBy('monitoring_tool_id');
            
        return view('admin.website_monitoring.configure', [
            'website' => $website,
            'monitoringTools' => $monitoringTools,
            'currentSettings' => $currentSettings,
            'title' => 'Configure monitoring for: ' . $website->name,
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Websites' => backpack_url('website'),
                $website->name => backpack_url('website/' . $website->id . '/show'),
                'Monitoring Configuration' => false,
            ],
        ]);
    }
    
    /**
     * Update the monitoring tools configuration for a website
     */
    public function update(Request $request, $websiteId)
    {
        $website = Website::findOrFail($websiteId);
        
        // Validate the request
        $validatedData = $request->validate([
            'tools' => 'required|array',
            'tools.*.id' => 'required|exists:monitoring_tools,id',
            'tools.*.enabled' => 'boolean',
            'tools.*.interval' => 'required|integer|min:1',
            'tools.*.threshold' => 'nullable|numeric',
            'tools.*.notify' => 'boolean',
            'tools.*.notify_discord' => 'boolean',
        ]);
        
        // Process each tool
        foreach ($validatedData['tools'] as $toolData) {
            $toolId = $toolData['id'];
            $enabled = $toolData['enabled'] ?? false;
            
            // Find or create a monitoring setting for this tool
            $setting = $website->monitoringSettings()
                ->firstOrCreate(
                    ['monitoring_tool_id' => $toolId],
                    [
                        'enabled' => false,
                        'interval' => MonitoringTool::find($toolId)->default_interval,
                        'threshold' => 0,
                        'notify' => false,
                        'notify_discord' => false,
                    ]
                );
            
            // Update the setting
            $setting->enabled = $enabled;
            if ($enabled) {
                $setting->interval = $toolData['interval'];
                $setting->threshold = $toolData['threshold'] ?? 0;
                $setting->notify = $toolData['notify'] ?? false;
                $setting->notify_discord = $toolData['notify_discord'] ?? false;
            }
            $setting->save();
        }
        
        \Alert::success('Monitoring settings updated successfully.')->flash();
        
        return redirect()->back();
    }
}
