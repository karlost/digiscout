<?php

namespace Tests\Unit\Services;

use App\Models\Website;
use App\Models\MonitoringTool;
use App\Services\Monitoring\PingService;
use Tests\TestCase;
use Mockery;

class PingServiceTest extends TestCase
{
    protected $pingService;
    protected $websiteMock;
    protected $toolMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->websiteMock = Mockery::mock(Website::class);
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com');
        $this->websiteMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        $this->toolMock = Mockery::mock(MonitoringTool::class);
        $this->toolMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Create the ping service with a partial mock
        $this->pingService = Mockery::mock(PingService::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ping_service_returns_success_result()
    {
        // Add missing method to mock
        $this->pingService->shouldReceive('extractHostname')->andReturn('example.com');
        
        // Create a mock to replace the actual ping call
        $pingStub = Mockery::mock('\JJG\Ping');
        $pingStub->shouldReceive('ping')->andReturn(42.5);
        
        // Ensure we use a specific ping implementation with predicable results
        $this->pingService->shouldReceive('createPing')->andReturn($pingStub);
        
        // Run the test
        $result = $this->pingService->check($this->websiteMock);
        
        // Assertions
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(42.5, $result['value']);
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
        $this->assertArrayHasKey('latency_ms', $result['additional_data']);
    }
    
    public function test_ping_service_returns_failure_result_on_high_ping()
    {
        // Add missing method to mock
        $this->pingService->shouldReceive('extractHostname')->andReturn('example.com');
        
        // Set a threshold that will cause the ping to be considered a failure
        $threshold = 50.0; // milliseconds
        
        // Create a ping stub that returns a high latency value
        $pingStub = Mockery::mock('\JJG\Ping');
        $pingStub->shouldReceive('ping')->andReturn(100.0);
        
        // Ensure we use a specific ping implementation with predicable results
        $this->pingService->shouldReceive('createPing')->andReturn($pingStub);
        
        // Run the test
        $result = $this->pingService->check($this->websiteMock, $threshold);
        
        // Assertions
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(100.0, $result['value']);
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
        $this->assertArrayHasKey('latency_ms', $result['additional_data']);
    }
    
    public function test_ping_service_returns_failure_result_on_unreachable_host()
    {
        // Add missing method to mock
        $this->pingService->shouldReceive('extractHostname')->andReturn('unreachable-host.com');
        
        // Create a ping stub that returns false (host unreachable)
        $pingStub = Mockery::mock('\JJG\Ping');
        $pingStub->shouldReceive('ping')->andReturn(false);
        
        // Ensure we use a specific ping implementation with predicable results
        $this->pingService->shouldReceive('createPing')->andReturn($pingStub);
        
        // Run the test
        $result = $this->pingService->check($this->websiteMock);
        
        // Assertions
        $this->assertEquals('failure', $result['status']);
        $this->assertNull($result['value']);
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertArrayHasKey('host', $result['additional_data']);
    }
}