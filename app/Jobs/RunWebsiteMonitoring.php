<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\WebsiteMonitoringSetting;
use App\Models\MonitoringResult;
use App\Notifications\MonitoringFailureNotification;
use App\Services\Monitoring\MonitoringServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class RunWebsiteMonitoring implements ShouldQueue
{
    use BusQueueable, InteractsWithQueue, SerializesModels;

    /**
     * The website to monitor
     */
    public $website;
    
    /**
     * The specific monitoring tool to use (optional)
     */
    public $toolCode;
    
    /**
     * Create a new job instance.
     */
    public function __construct(Website $website, ?string $toolCode = null)
    {
        $this->website = $website;
        $this->toolCode = $toolCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $website = $this->website;
        $toolCode = $this->toolCode;
        
        // Skip inactive websites
        if (!$website->status) {
            return;
        }
        
        try {
            // If a specific tool code is provided, attempt to get it from the service container
            if ($toolCode) {
                // Try to get the monitoring tool from the database
                $tool = MonitoringTool::where('code', $toolCode)->first();
                
                if (!$tool) {
                    logger()->error("Tool not found with code: {$toolCode}");
                    return;
                }
                
                // Try to get a single monitoring setting for this website and tool
                $setting = WebsiteMonitoringSetting::where('website_id', $website->id)
                    ->where('monitoring_tool_id', $tool->id)
                    ->where('enabled', true)
                    ->first();
                
                if (!$setting) {
                    return; // No active setting for this tool
                }
                
                try {
                    // Get monitoring service from the container
                    $service = app()->has("monitoring.{$toolCode}") 
                        ? app("monitoring.{$toolCode}") 
                        : null;
                    
                    if (!$service) {
                        // Try to find the service in the services collection
                        $services = app('monitoring.services');
                        $service = collect($services)->first(function ($svc) use ($toolCode) {
                            return $svc->getCode() === $toolCode;
                        });
                    }
                    
                    if (!$service) {
                        logger()->error("Service not found for tool code: {$toolCode}");
                        return;
                    }
                    
                    // Run the monitoring check
                    $result = $service->check($website, $setting->threshold);
                    
                    // Create a monitoring result record
                    $monitoringResult = MonitoringResult::create([
                        'website_id' => $website->id,
                        'monitoring_tool_id' => $tool->id,
                        'status' => $result['status'],
                        'value' => $result['value'],
                        'check_time' => now(),
                        'additional_data' => $result['additional_data']
                    ]);
                    
                    // Send notification if check failed and notifications are enabled
                    if ($result['status'] === 'failure' && $setting->notify) {
                        // Send notifications to all admin users
                        $adminUsers = User::where('is_admin', true)->get();
                        if ($adminUsers->isNotEmpty()) {
                            Notification::send(
                                $adminUsers, 
                                new MonitoringFailureNotification($monitoringResult)
                            );
                        }
                    }
                } catch (\Exception $e) {
                    logger()->error("Monitoring error: {$e->getMessage()}", [
                        'website_id' => $website->id,
                        'tool_id' => $tool->id,
                        'exception' => $e
                    ]);
                }
            }
        } catch (\Exception $e) {
            logger()->error("Job execution error: {$e->getMessage()}", [
                'website_id' => $website->id,
                'tool_code' => $toolCode,
                'exception' => $e
            ]);
        }
    }
}