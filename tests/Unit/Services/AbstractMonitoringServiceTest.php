<?php

namespace Tests\Unit\Services;

use App\Models\Website;
use App\Models\MonitoringTool;
use App\Services\Monitoring\AbstractMonitoringService;
use Tests\TestCase;
use Mockery;

class AbstractMonitoringServiceTest extends TestCase
{
    protected $websiteMock;
    protected $toolMock;
    protected $serviceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->websiteMock = Mockery::mock(Website::class);
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com');
        $this->websiteMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        $this->toolMock = Mockery::mock(MonitoringTool::class);
        $this->toolMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Create a concrete implementation of the abstract class for testing
        $this->serviceMock = Mockery::mock(AbstractMonitoringService::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->serviceMock->shouldReceive('performCheck')->andReturn([
            'status' => 'success',
            'value' => 123.45,
            'additional_data' => ['message' => 'Test message']
        ]);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_run_monitoring_check_returns_result_object()
    {
        $result = $this->serviceMock->check($this->websiteMock);
        
        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(123.45, $result['value']);
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertEquals('Test message', $result['additional_data']['message']);
    }
    
    public function test_run_monitoring_check_handles_exceptions()
    {
        // Setup an implementation that throws an exception
        $serviceMock = Mockery::mock(AbstractMonitoringService::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $serviceMock->shouldReceive('performCheck')->andThrow(new \Exception('Test exception'));
        
        $result = $serviceMock->check($this->websiteMock);
        
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(0, $result['value']);
        $this->assertArrayHasKey('message', $result['additional_data']);
        $this->assertEquals('Test exception', $result['additional_data']['message']);
        $this->assertArrayHasKey('exception', $result['additional_data']);
    }
}