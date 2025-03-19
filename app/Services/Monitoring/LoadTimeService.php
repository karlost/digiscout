<?php

namespace App\Services\Monitoring;

use App\Models\Website;
use Illuminate\Support\Facades\Http;

class LoadTimeService extends AbstractMonitoringService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = 'load_time';
        $this->name = 'Load Time';
        $this->description = 'Measures total time to load a website';
    }
    
    /**
     * Perform the monitoring check
     *
     * @param Website $website
     * @param float|null $threshold Load time threshold in seconds
     * @return array
     */
    protected function performCheck(Website $website, ?float $threshold = null): array
    {
        $startTime = microtime(true);
        
        // Send HTTP request
        $response = Http::withoutVerifying() // Skip SSL verification
            ->timeout(30) // 30 second timeout
            ->get($website->url);
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        // Format load time to 3 decimals
        $loadTimeFormatted = number_format($loadTime, 3);
        
        // Status is success if under threshold, or if no threshold is set
        $status = 'success';
        if ($threshold !== null && $loadTime > $threshold) {
            $status = 'failure';
        }
        
        return [
            'status' => $status,
            'value' => $loadTime,
            'additional_data' => [
                'message' => "Load time: {$loadTimeFormatted} seconds",
                'load_time_seconds' => $loadTime,
                'status_code' => $response->status(),
                'content_size_bytes' => strlen($response->body()),
            ]
        ];
    }
}