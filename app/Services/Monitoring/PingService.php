<?php

namespace App\Services\Monitoring;

use App\Models\Website;
use JJG\Ping;

class PingService extends AbstractMonitoringService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = 'ping';
        $this->name = 'Ping';
        $this->description = 'Checks server availability using ICMP ping and measures response time';
    }
    
    /**
     * Perform the monitoring check
     *
     * @param Website $website
     * @param float|null $threshold Ping response time threshold in milliseconds
     * @return array
     */
    /**
     * Create a new Ping object for testing the given host
     * This method allows us to better test the PingService by mocking this
     * method to return a predictable Ping object.
     * 
     * @param string $host The hostname to create a ping object for
     * @return \JJG\Ping
     */
    protected function createPing(string $host): \JJG\Ping
    {
        return new Ping($host);
    }
    
    /**
     * Perform the monitoring check
     *
     * @param Website $website The website to check
     * @param float|null $threshold Ping response time threshold in milliseconds
     * @return array
     */
    protected function performCheck(Website $website, ?float $threshold = null): array
    {
        $host = $this->extractHostname($website->url);
        $ping = $this->createPing($host);
        
        // Try to ping 3 times and take average
        $latencies = [];
        for ($i = 0; $i < 3; $i++) {
            $result = $ping->ping();
            if ($result !== false) {
                $latencies[] = $result;
            }
        }
        
        // Calculate average latency
        $latency = !empty($latencies) ? array_sum($latencies) / count($latencies) : false;
        
        if ($latency === false) {
            return [
                'status' => 'failure',
                'value' => null,
                'additional_data' => [
                    'message' => 'Host unreachable',
                    'host' => $host
                ]
            ];
        }
        
        // Check if latency is above threshold
        $status = 'success';
        if ($threshold !== null && $latency > $threshold) {
            $status = 'failure';
        }
        
        return [
            'status' => $status,
            'value' => $latency,
            'additional_data' => [
                'message' => 'Host reachable',
                'host' => $host,
                'latency_ms' => $latency
            ]
        ];
    }
}