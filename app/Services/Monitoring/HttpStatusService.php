<?php

namespace App\Services\Monitoring;

use App\Models\Website;
use Illuminate\Support\Facades\Http;

class HttpStatusService extends AbstractMonitoringService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = 'http_status';
        $this->name = 'HTTP Status';
        $this->description = 'Checks HTTP response status code and detects redirects';
    }
    
    /**
     * Perform the monitoring check
     *
     * @param Website $website
     * @param float|null $threshold Not used for this service
     * @return array
     */
    protected function performCheck(Website $website, ?float $threshold = null): array
    {
        // Use laravel HTTP client with redirect tracking
        $response = Http::withoutVerifying() // Skip SSL verification for broader compatibility
            ->withOptions([
                'allow_redirects' => [
                    'track_redirects' => true
                ],
                'timeout' => 10.0, // 10 second timeout
            ])
            ->get($website->url);
        
        $statusCode = $response->status();
        $redirects = $response->handlerStats()['redirect_url'] ?? null;
        $redirectCount = $response->handlerStats()['redirect_count'] ?? 0;
        
        // Success if HTTP 2xx, failure for other status codes
        $status = ($statusCode >= 200 && $statusCode < 300) ? 'success' : 'failure';
        
        return [
            'status' => $status,
            'value' => (float) $statusCode,
            'additional_data' => [
                'message' => "HTTP Status: $statusCode",
                'status_code' => $statusCode,
                'redirect_count' => $redirectCount,
                'final_url' => $redirects ?: $website->url,
            ]
        ];
    }
}