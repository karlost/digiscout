<?php

namespace Tests\Unit\Models;

use App\Models\Website;
use App\Models\MonitoringResult;
use App\Models\MonitoringTool;
use App\Models\WebsiteMonitoringSetting;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_has_correct_fillable_attributes()
    {
        $website = new Website();
        
        $this->assertEquals([
            'url',
            'name',
            'description',
            'status',
        ], $website->getFillable());
    }
    
    public function test_website_has_correct_casts()
    {
        $website = new Website();
        
        $this->assertArrayHasKey('status', $website->getCasts());
        $this->assertEquals('boolean', $website->getCasts()['status']);
    }
    
    public function test_website_has_many_monitoring_settings()
    {
        $website = Website::factory()->create();
        $tools = [];
        
        // Create unique monitoring tools
        for ($i = 0; $i < 3; $i++) {
            $tools[] = MonitoringTool::factory()->create(['code' => 'tool_' . uniqid()]);
        }
        
        // Create settings with the unique tools
        foreach ($tools as $tool) {
            WebsiteMonitoringSetting::factory()->create([
                'website_id' => $website->id,
                'monitoring_tool_id' => $tool->id
            ]);
        }
        
        $this->assertCount(3, $website->monitoringSettings);
        $this->assertInstanceOf(WebsiteMonitoringSetting::class, $website->monitoringSettings->first());
    }
    
    public function test_website_has_many_monitoring_results()
    {
        $website = Website::factory()->create();
        $tools = [];
        
        // Create unique monitoring tools
        for ($i = 0; $i < 5; $i++) {
            $tools[] = MonitoringTool::factory()->create(['code' => 'result_tool_' . uniqid()]);
        }
        
        // Create results with the unique tools
        foreach ($tools as $tool) {
            MonitoringResult::factory()->create([
                'website_id' => $website->id,
                'monitoring_tool_id' => $tool->id
            ]);
        }
        
        $this->assertCount(5, $website->monitoringResults);
        $this->assertInstanceOf(MonitoringResult::class, $website->monitoringResults->first());
    }
}