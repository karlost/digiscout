<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\WebsiteMonitoringSetting;
use App\Jobs\RunWebsiteMonitoring;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class RunMonitoringTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_command_dispatches_jobs_for_all_websites()
    {
        // Mock the Queue facade
        Queue::fake();
        
        // Create test data
        $website1 = Website::factory()->create(['status' => true]);
        $website2 = Website::factory()->create(['status' => true]);
        $website3 = Website::factory()->create(['status' => false]); // inactive
        
        $tool1 = MonitoringTool::factory()->create(['code' => 'ping']);
        $tool2 = MonitoringTool::factory()->create(['code' => 'http']);
        
        // Create settings
        WebsiteMonitoringSetting::factory()->create([
            'website_id' => $website1->id,
            'monitoring_tool_id' => $tool1->id,
            'enabled' => true,
        ]);
        
        WebsiteMonitoringSetting::factory()->create([
            'website_id' => $website1->id,
            'monitoring_tool_id' => $tool2->id,
            'enabled' => true,
        ]);
        
        WebsiteMonitoringSetting::factory()->create([
            'website_id' => $website2->id,
            'monitoring_tool_id' => $tool1->id,
            'enabled' => true,
        ]);
        
        WebsiteMonitoringSetting::factory()->create([
            'website_id' => $website3->id, // inactive website
            'monitoring_tool_id' => $tool1->id,
            'enabled' => true,
        ]);
        
        WebsiteMonitoringSetting::factory()->create([
            'website_id' => $website2->id,
            'monitoring_tool_id' => $tool2->id,
            'enabled' => false, // disabled setting
        ]);
        
        // Run the command
        $this->artisan('monitoring:run')
            ->expectsOutput('Starting website monitoring check...')
            ->assertExitCode(0);
        
        // Verify that the jobs were dispatched
        Queue::assertPushed(RunWebsiteMonitoring::class, 3); // Only 3 enabled settings for active websites
        
        Queue::assertPushed(function (RunWebsiteMonitoring $job) use ($website1, $tool1) {
            return $job->website->id === $website1->id && $job->toolCode === 'ping';
        });
        
        Queue::assertPushed(function (RunWebsiteMonitoring $job) use ($website1, $tool2) {
            return $job->website->id === $website1->id && $job->toolCode === 'http';
        });
        
        Queue::assertPushed(function (RunWebsiteMonitoring $job) use ($website2, $tool1) {
            return $job->website->id === $website2->id && $job->toolCode === 'ping';
        });
        
        // Verify that jobs were not dispatched for inactive settings
        Queue::assertNotPushed(function (RunWebsiteMonitoring $job) use ($website2, $tool2) {
            return $job->website->id === $website2->id && $job->toolCode === 'http';
        });
        
        // Verify that jobs were not dispatched for inactive websites
        Queue::assertNotPushed(function (RunWebsiteMonitoring $job) use ($website3) {
            return $job->website->id === $website3->id;
        });
    }
    
    public function test_command_handles_no_websites()
    {
        Queue::fake();
        
        // Run the command with no websites in the database
        $this->artisan('monitoring:run')
            ->expectsOutput('Starting website monitoring check...')
            ->assertExitCode(0);
        
        // No jobs should be dispatched
        Queue::assertNotPushed(RunWebsiteMonitoring::class);
    }
    
    public function test_command_handles_websites_with_no_monitoring_settings()
    {
        Queue::fake();
        
        // Create a website but no monitoring settings
        $website = Website::factory()->create(['status' => true]);
        
        // Run the command
        $this->artisan('monitoring:run')
            ->expectsOutput('Starting website monitoring check...')
            ->assertExitCode(0);
        
        // No jobs should be dispatched
        Queue::assertNotPushed(RunWebsiteMonitoring::class);
    }
}