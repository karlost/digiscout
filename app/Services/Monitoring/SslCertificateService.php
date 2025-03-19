<?php

namespace App\Services\Monitoring;

use App\Models\Website;

class SslCertificateService extends AbstractMonitoringService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = 'ssl_certificate';
        $this->name = 'SSL Certificate';
        $this->description = 'Checks SSL certificate validity and expiration';
    }
    
    /**
     * Create an SSL socket client and return the socket and certificate info
     * This is extracted to a separate method to make the service more testable
     * 
     * @param string $host The hostname to connect to
     * @return array With keys: success, socket, certificate, error
     */
    protected function createSslSocketClient(string $host): array
    {
        // Get SSL certificate info - timeout after 5 seconds
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'capture_peer_cert' => true,
                'timeout' => 5,
            ]
        ]);
        
        // Try to connect using SSL
        $socket = @stream_socket_client(
            "ssl://{$host}:443", 
            $errno, 
            $errstr, 
            5, 
            STREAM_CLIENT_CONNECT, 
            $context
        );
        
        if (!$socket) {
            return [
                'success' => false,
                'error' => $errstr ?: 'Unknown SSL connection error'
            ];
        }
        
        // Get certificate details
        $params = stream_context_get_params($socket);
        $certificate = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        
        return [
            'success' => true,
            'socket' => $socket,
            'certificate' => $certificate
        ];
    }
    
    /**
     * Perform the actual monitoring check
     *
     * @param Website $website The website to check
     * @param float|null $threshold Days until expiration threshold
     * @return array
     */
    protected function performCheck(Website $website, ?float $threshold = null): array
    {
        $host = $this->extractHostname($website->url);
        
        // Get the certificate info
        $sslInfo = $this->createSslSocketClient($host);
        
        if (!$sslInfo['success']) {
            return [
                'status' => 'failure',
                'value' => 0,
                'additional_data' => [
                    'message' => "SSL connection failed: {$sslInfo['error']}",
                    'host' => $host,
                    'error' => $sslInfo['error'],
                ]
            ];
        }
        
        $certificate = $sslInfo['certificate'];
        
        // Calculate days until expiration
        $validTo = $certificate['validTo_time_t'];
        $daysUntilExpiration = floor(($validTo - time()) / 86400);
        
        // Status is success if expiration is beyond threshold or no threshold is set
        // Default threshold: 14 days
        $threshold = $threshold ?? 14;
        $status = ($daysUntilExpiration > $threshold) ? 'success' : 'failure';
        
        return [
            'status' => $status,
            'value' => $daysUntilExpiration,
            'additional_data' => [
                'message' => "SSL certificate expires in {$daysUntilExpiration} days",
                'host' => $host,
                'valid_from' => date('Y-m-d H:i:s', $certificate['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $validTo),
                'issuer' => $certificate['issuer']['CN'] ?? null,
                'subject' => $certificate['subject']['CN'] ?? null,
            ]
        ];
    }
}