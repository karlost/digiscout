<?php

namespace Tests\Unit\Models;

use App\Models\MonitoringResult;
use App\Models\Website;
use App\Models\MonitoringTool;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonitoringResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_has_correct_fillable_attributes()
    {
        $result = new MonitoringResult();
        
        $this->assertEquals([
            'website_id',
            'monitoring_tool_id',
            'status',
            'value',
            'check_time',
            'additional_data',
        ], $result->getFillable());
    }
    
    public function test_result_has_correct_casts()
    {
        $result = new MonitoringResult();
        
        $this->assertArrayHasKey('check_time', $result->getCasts());
        $this->assertArrayHasKey('additional_data', $result->getCasts());
        $this->assertEquals('datetime', $result->getCasts()['check_time']);
        $this->assertEquals('array', $result->getCasts()['additional_data']);
    }
    
    public function test_result_belongs_to_website()
    {
        $website = Website::factory()->create();
        $tool = MonitoringTool::factory()->create(['code' => 'result_web_test_' . uniqid()]);
        
        $result = MonitoringResult::factory()->create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id
        ]);
        
        $this->assertInstanceOf(Website::class, $result->website);
        $this->assertEquals($website->id, $result->website->id);
    }
    
    public function test_result_belongs_to_monitoring_tool()
    {
        $tool = MonitoringTool::factory()->create(['code' => 'result_tool_test_' . uniqid()]);
        $result = MonitoringResult::factory()->create([
            'monitoring_tool_id' => $tool->id
        ]);
        
        $this->assertInstanceOf(MonitoringTool::class, $result->monitoringTool);
        $this->assertEquals($tool->id, $result->monitoringTool->id);
    }
}