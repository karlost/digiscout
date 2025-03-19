<?php

namespace App\Services\Monitoring;

use App\Models\Website;

interface MonitoringServiceInterface
{
    /**
     * Get the unique code for this monitoring tool
     *
     * @return string
     */
    public function getCode(): string;
    
    /**
     * Get the name of this monitoring tool
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * Get the description of this monitoring tool
     *
     * @return string
     */
    public function getDescription(): string;
    
    /**
     * Check a website with this monitoring tool
     *
     * @param Website $website
     * @param float|null $threshold
     * @return array The monitoring result with keys: status, value, additional_data
     */
    public function check(Website $website, ?float $threshold = null): array;
}