<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\WebsiteMonitoringSetting;
use App\Models\MonitoringResult;
use App\Services\Monitoring\MonitoringServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:run {--website=} {--tool=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run website monitoring checks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting website monitoring check...');
        
        $websiteId = $this->option('website');
        $toolCode = $this->option('tool');
        
        // Get all active websites
        $websitesQuery = Website::where('status', true);
        
        // Filter by specific website if provided
        if ($websiteId) {
            $websitesQuery->where('id', $websiteId);
        }
        
        $websites = $websitesQuery->get();
        
        if ($websites->isEmpty()) {
            $this->info('No active websites found to monitor.');
            return 0;
        }
        
        foreach ($websites as $website) {
            // Get enabled monitoring settings for this website
            $settingsQuery = WebsiteMonitoringSetting::where('website_id', $website->id)
                ->where('enabled', true)
                ->with('monitoringTool');
            
            // Filter by specific tool if provided
            if ($toolCode) {
                $settingsQuery->whereHas('monitoringTool', function ($query) use ($toolCode) {
                    $query->where('code', $toolCode);
                });
            }
            
            $settings = $settingsQuery->get();
            
            if ($settings->isEmpty()) {
                continue;
            }
            
            foreach ($settings as $setting) {
                $tool = $setting->monitoringTool;
                
                // Dispatch job for each website/tool combination
                dispatch(new \App\Jobs\RunWebsiteMonitoring($website, $tool->code));
            }
        }
        
        return 0;
    }
}
