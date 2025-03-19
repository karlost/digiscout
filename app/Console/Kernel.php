<?php

namespace App\Console;

use App\Models\Website;
use App\Models\WebsiteMonitoringSetting;
use App\Jobs\RunWebsiteMonitoring;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Command to run all monitoring checks at once
        $schedule->command('monitoring:run')
            ->hourly()
            ->appendOutputTo(storage_path('logs/monitoring.log'));
            
        // Queue individual website monitoring tasks with proper intervals
        $schedule->call(function () {
            // Get all active websites with their monitoring settings
            $websites = Website::where('status', true)->get();
            
            foreach ($websites as $website) {
                // Get all monitoring settings for this website
                $settings = WebsiteMonitoringSetting::where('website_id', $website->id)
                    ->where('enabled', true)
                    ->with('monitoringTool')
                    ->get();
                    
                foreach ($settings as $setting) {
                    $tool = $setting->monitoringTool;
                    
                    // Check if it's time to run this monitoring check
                    // Get the latest result for this website and tool
                    $lastCheck = \App\Models\MonitoringResult::where('website_id', $website->id)
                        ->where('monitoring_tool_id', $tool->id)
                        ->latest('check_time')
                        ->first();
                        
                    $shouldRun = true;
                    if ($lastCheck) {
                        // Calculate when the next check should occur based on the interval
                        $nextCheckTime = $lastCheck->check_time->addMinutes($setting->interval);
                        
                        // Only run if the next check time has passed
                        $shouldRun = now()->gte($nextCheckTime);
                    }
                    
                    if ($shouldRun) {
                        // Dispatch a job for this tool
                        RunWebsiteMonitoring::dispatch($website, $tool->code);
                    }
                }
            }
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}