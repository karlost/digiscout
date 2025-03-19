<?php

namespace App\Services\Monitoring;

use App\Models\Website;

class DnsCheckService extends AbstractMonitoringService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = 'dns_check';
        $this->name = 'DNS Check';
        $this->description = 'Verifies DNS records and configurations for websites';
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
        $host = $this->extractHostname($website->url);
        
        // Get A records
        $aRecords = dns_get_record($host, DNS_A);
        
        // Get MX records
        $mxRecords = dns_get_record($host, DNS_MX);
        
        // Get TXT records
        $txtRecords = dns_get_record($host, DNS_TXT);
        
        // Get CNAME records
        $cnameRecords = dns_get_record($host, DNS_CNAME);
        
        // Get NS records
        $nsRecords = dns_get_record($host, DNS_NS);
        
        // Combine all records
        $allRecords = [
            'a' => $aRecords,
            'mx' => $mxRecords,
            'txt' => $txtRecords,
            'cname' => $cnameRecords,
            'ns' => $nsRecords,
        ];
        
        // Check if there are A or CNAME records (the most critical for websites)
        $status = 'success';
        $message = 'DNS records found';
        
        if (empty($aRecords) && empty($cnameRecords)) {
            $status = 'failure';
            $message = 'No A or CNAME records found';
        }
        
        return [
            'status' => $status,
            'value' => count($aRecords) + count($cnameRecords), // Count of most critical records
            'additional_data' => [
                'message' => $message,
                'host' => $host,
                'records' => $allRecords,
            ]
        ];
    }
}