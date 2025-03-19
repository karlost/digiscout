<?php

namespace Tests\Unit\Services;

use App\Models\Website;
use App\Services\Monitoring\SslCertificateService;
use Tests\TestCase;
use Mockery;

class SslCertificateServiceTest extends TestCase
{
    protected $sslService;
    protected $websiteMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->websiteMock = Mockery::mock(Website::class);
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com');
        
        // Create the SSL service with a mock
        $this->sslService = Mockery::mock(SslCertificateService::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ssl_service_returns_success_for_valid_certificate()
    {
        // Add missing method to mock
        $this->sslService->shouldReceive('extractHostname')->andReturn('example.com');
        
        // Mock the stream_socket_client to return a valid socket
        $this->sslService->shouldReceive('createSslSocketClient')->andReturnUsing(function() {
            // Mock a valid certificate expiring in 30 days
            $futureTimestamp = time() + (30 * 86400);
            
            return [
                'success' => true,
                'certificate' => [
                    'validFrom_time_t' => time() - (90 * 86400),
                    'validTo_time_t' => $futureTimestamp,
                    'subject' => ['CN' => 'example.com'],
                    'issuer' => ['CN' => 'Let\'s Encrypt Authority X3']
                ]
            ];
        });
        
        // Run the test
        $result = $this->sslService->check($this->websiteMock);
        
        // Assertions
        $this->assertEquals('success', $result['status']);
        $this->assertGreaterThan(14, $result['value']); // Days until expiration should be > 14
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
        $this->assertArrayHasKey('valid_from', $result['additional_data']);
        $this->assertArrayHasKey('valid_to', $result['additional_data']);
    }
    
    public function test_ssl_service_returns_warning_for_certificate_expiring_soon()
    {
        // Add missing method to mock
        $this->sslService->shouldReceive('extractHostname')->andReturn('example.com');
        
        // Mock the stream_socket_client to return a socket with certificate expiring soon
        $this->sslService->shouldReceive('createSslSocketClient')->andReturnUsing(function() {
            // Mock a certificate expiring in 6 days
            $nearExpirationTimestamp = time() + (6 * 86400);
            
            return [
                'success' => true,
                'certificate' => [
                    'validFrom_time_t' => time() - (85 * 86400),
                    'validTo_time_t' => $nearExpirationTimestamp,
                    'subject' => ['CN' => 'example.com'],
                    'issuer' => ['CN' => 'Let\'s Encrypt Authority X3']
                ]
            ];
        });
        
        // Run the test with default threshold (14 days)
        $result = $this->sslService->check($this->websiteMock);
        
        // Assertions
        $this->assertEquals('failure', $result['status']);
        $this->assertLessThan(14, $result['value']); // Days until expiration should be < 14
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
        $this->assertStringContainsString('expires in', $result['additional_data']['message']);
    }
    
    public function test_ssl_service_returns_failure_for_expired_certificate()
    {
        // Add missing method to mock
        $this->sslService->shouldReceive('extractHostname')->andReturn('example.com');
        
        // Mock the stream_socket_client to return a socket with expired certificate
        $this->sslService->shouldReceive('createSslSocketClient')->andReturnUsing(function() {
            // Mock an expired certificate
            $expiredTimestamp = time() - (1 * 86400);
            
            return [
                'success' => true,
                'certificate' => [
                    'validFrom_time_t' => time() - (91 * 86400),
                    'validTo_time_t' => $expiredTimestamp,
                    'subject' => ['CN' => 'example.com'],
                    'issuer' => ['CN' => 'Let\'s Encrypt Authority X3']
                ]
            ];
        });
        
        // Run the test
        $result = $this->sslService->check($this->websiteMock);
        
        // Assertions
        $this->assertEquals('failure', $result['status']);
        $this->assertLessThan(0, $result['value']); // Days until expiration should be negative
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
    }
    
    public function test_ssl_service_returns_failure_for_invalid_certificate()
    {
        // Add missing method to mock
        $this->sslService->shouldReceive('extractHostname')->andReturn('invalid-ssl.com');
        
        // Mock the stream_socket_client to fail
        $this->sslService->shouldReceive('createSslSocketClient')->andReturn([
            'success' => false,
            'error' => 'SSL connection failed: unable to verify the certificate'
        ]);
        
        // Run the test
        $result = $this->sslService->check($this->websiteMock);
        
        // Assertions
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(0, $result['value']);
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
        $this->assertArrayHasKey('error', $result['additional_data']);
    }
}