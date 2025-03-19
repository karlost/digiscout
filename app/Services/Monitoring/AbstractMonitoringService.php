<?php

namespace App\Services\Monitoring;

use App\Models\Website;

abstract class AbstractMonitoringService implements MonitoringServiceInterface
{
    /**
     * The code identifier for this monitoring tool
     *
     * @var string
     */
    protected string $code;
    
    /**
     * The human-readable name for this monitoring tool
     *
     * @var string
     */
    protected string $name;
    
    /**
     * The description of what this monitoring tool does
     *
     * @var string
     */
    protected string $description;
    
    /**
     * Get the unique code for this monitoring tool
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
    
    /**
     * Get the name of this monitoring tool
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get the description of this monitoring tool
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Check a website with this monitoring tool
     *
     * @param Website $website
     * @param float|null $threshold
     * @return array The monitoring result with keys: status, value, additional_data
     */
    public function check(Website $website, ?float $threshold = null): array
    {
        try {
            // Run the concrete implementation's check
            return $this->performCheck($website, $threshold);
        } catch (\Exception $e) {
            // Handle any exceptions by returning a failure result
            return [
                'status' => 'failure',
                'value' => 0,
                'additional_data' => [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }
    
    /**
     * Perform the actual monitoring check
     * This must be implemented by concrete service classes
     *
     * @param Website $website
     * @param float|null $threshold
     * @return array The monitoring result with keys: status, value, additional_data
     */
    abstract protected function performCheck(Website $website, ?float $threshold = null): array;
    
    /**
     * Extract the hostname from a URL
     *
     * @param string $url
     * @return string
     */
    protected function extractHostname(string $url): string
    {
        $parsedUrl = parse_url($url);
        return $parsedUrl['host'] ?? '';
    }
}