<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RunWebsiteMonitoring;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\MonitoringResult;
use App\Services\Monitoring\MonitoringServiceInterface;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class RunWebsiteMonitoringTest extends TestCase
{
    use RefreshDatabase;
    
    protected $website;
    protected $tool;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->website = Website::factory()->create([
            'url' => 'https://example.com',
            'status' => true
        ]);
        
        $this->tool = MonitoringTool::factory()->create([
            'code' => 'test_tool',
            'name' => 'Test Tool'
        ]);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_executes_monitoring_service()
    {
        // Create a monitoring setting
        \App\Models\WebsiteMonitoringSetting::factory()->create([
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
            'threshold' => 100,
            'enabled' => true,
        ]);
    
        // Create a mock of the monitoring service
        $serviceMock = Mockery::mock(MonitoringServiceInterface::class);
        
        // Set up expectations for the mock
        $serviceMock->shouldReceive('getCode')
            ->andReturn('test_tool');
            
        $serviceMock->shouldReceive('check')
            ->once()
            ->with(Mockery::type(Website::class), Mockery::any())
            ->andReturn([
                'status' => 'success',
                'value' => 200,
                'additional_data' => ['message' => 'Test passed']
            ]);
        
        // Bind the mock to the container
        $this->app->instance('monitoring.test_tool', $serviceMock);
        
        // Create and handle the job
        $job = new RunWebsiteMonitoring($this->website, 'test_tool');
        $job->handle();
        
        // Check that a result was created
        $this->assertDatabaseHas('monitoring_results', [
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
            'status' => 'success',
            'value' => 200,
        ]);
    }
    
    public function test_job_handles_invalid_tool_code()
    {
        // Create a job with a non-existent tool code
        $job = new RunWebsiteMonitoring($this->website, 'invalid_tool');
        
        // This should log an error but not crash
        $job->handle();
        
        // No results should be created
        $this->assertDatabaseMissing('monitoring_results', [
            'website_id' => $this->website->id,
        ]);
    }
    
    public function test_job_handles_service_resolution_error()
    {
        // Create a monitoring setting but with a tool that exists
        \App\Models\WebsiteMonitoringSetting::factory()->create([
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
            'threshold' => 100,
            'enabled' => true,
        ]);
        
        // Do not bind any service to the container
        // The job should catch the service resolution exception
        
        // Create and handle the job
        $job = new RunWebsiteMonitoring($this->website, 'test_tool');
        
        // This should not throw an exception
        $job->handle();
        
        // No results should be created
        $this->assertDatabaseMissing('monitoring_results', [
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
        ]);
    }
}