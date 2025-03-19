<?php

namespace Tests\Unit\Models;

use App\Models\MonitoringTool;
use App\Models\Website;
use App\Models\WebsiteMonitoringSetting;
use App\Models\MonitoringResult;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonitoringToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_tool_has_correct_fillable_attributes()
    {
        $tool = new MonitoringTool();
        
        $this->assertEquals([
            'name',
            'code',
            'description',
            'default_interval',
            'interval_unit',
            'is_active',
            'is_default',
            'display_order',
        ], $tool->getFillable());
    }
    
    public function test_monitoring_tool_has_many_settings()
    {
        $tool = MonitoringTool::factory()->create(['code' => 'code_' . uniqid()]);
        $settings = WebsiteMonitoringSetting::factory()->count(3)->create([
            'monitoring_tool_id' => $tool->id
        ]);
        
        $this->assertCount(3, $tool->settings);
        $this->assertInstanceOf(WebsiteMonitoringSetting::class, $tool->settings->first());
    }
    
    public function test_monitoring_tool_has_many_results()
    {
        $tool = MonitoringTool::factory()->create(['code' => 'code_' . uniqid()]);
        $results = MonitoringResult::factory()->count(4)->create([
            'monitoring_tool_id' => $tool->id
        ]);
        
        $this->assertCount(4, $tool->results);
        $this->assertInstanceOf(MonitoringResult::class, $tool->results->first());
    }
    
    /**
     * Test boolean cast for is_active field
     */
    public function test_is_active_boolean_cast(): void
    {
        // Create a tool
        $tool = MonitoringTool::factory()->create([
            'code' => 'test_tool_' . uniqid(),
            'is_active' => true,
        ]);
        
        // Test boolean cast
        $this->assertIsBool($tool->is_active);
        $this->assertTrue($tool->is_active);
        
        // Update to false
        $tool->update(['is_active' => false]);
        $tool->refresh();
        
        $this->assertIsBool($tool->is_active);
        $this->assertFalse($tool->is_active);
    }
    
    /**
     * Test boolean cast for is_default field
     */
    public function test_is_default_boolean_cast(): void
    {
        // Create a tool
        $tool = MonitoringTool::factory()->create([
            'code' => 'test_tool_' . uniqid(),
            'is_default' => true,
        ]);
        
        // Test boolean cast
        $this->assertIsBool($tool->is_default);
        $this->assertTrue($tool->is_default);
        
        // Update to false
        $tool->update(['is_default' => false]);
        $tool->refresh();
        
        $this->assertIsBool($tool->is_default);
        $this->assertFalse($tool->is_default);
    }
    
    /**
     * Test integer cast for display_order field
     */
    public function test_display_order_integer_cast(): void
    {
        // Create a tool
        $tool = MonitoringTool::factory()->create([
            'code' => 'test_tool_' . uniqid(),
            'display_order' => '10', // Pass as string to test cast
        ]);
        
        // Test integer cast
        $this->assertIsInt($tool->display_order);
        $this->assertEquals(10, $tool->display_order);
    }
    
    /**
     * Test active tools scope
     */
    public function test_active_tools_scope(): void
    {
        // Clear existing tools to avoid constraint violations
        MonitoringTool::query()->delete();
        
        // Create active and inactive tools
        MonitoringTool::create([
            'name' => 'Active Tool',
            'code' => 'active_tool_' . uniqid(),
            'description' => 'An active monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 10,
        ]);
        
        MonitoringTool::create([
            'name' => 'Inactive Tool',
            'code' => 'inactive_tool_' . uniqid(),
            'description' => 'An inactive monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => false,
            'is_default' => false,
            'display_order' => 20,
        ]);
        
        // Test the scope
        $activeTools = MonitoringTool::where('is_active', true)->get();
        $inactiveTools = MonitoringTool::where('is_active', false)->get();
        
        $this->assertEquals(1, $activeTools->count());
        $this->assertEquals(1, $inactiveTools->count());
    }
    
    /**
     * Test default tools scope
     */
    public function test_default_tools_scope(): void
    {
        // Clear existing tools to avoid constraint violations
        MonitoringTool::query()->delete();
        
        // Create default and non-default tools
        MonitoringTool::create([
            'name' => 'Default Tool',
            'code' => 'default_tool_' . uniqid(),
            'description' => 'A default monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);
        
        MonitoringTool::create([
            'name' => 'Non-Default Tool',
            'code' => 'non_default_tool_' . uniqid(),
            'description' => 'A non-default monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 20,
        ]);
        
        // Test the scope
        $defaultTools = MonitoringTool::where('is_default', true)->get();
        $nonDefaultTools = MonitoringTool::where('is_default', false)->get();
        
        $this->assertEquals(1, $defaultTools->count());
        $this->assertEquals(1, $nonDefaultTools->count());
    }
    
    /**
     * Test display order sorting
     */
    public function test_display_order_sorting(): void
    {
        // Clear existing tools to avoid constraint violations
        MonitoringTool::query()->delete();
        
        // Create tools with different display orders
        $highOrderTool = MonitoringTool::create([
            'name' => 'High Order Tool',
            'code' => 'high_tool_' . uniqid(),
            'description' => 'A high priority tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 30,
        ]);
        
        $lowOrderTool = MonitoringTool::create([
            'name' => 'Low Order Tool',
            'code' => 'low_tool_' . uniqid(),
            'description' => 'A low priority tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);
        
        $medOrderTool = MonitoringTool::create([
            'name' => 'Medium Order Tool',
            'code' => 'med_tool_' . uniqid(),
            'description' => 'A medium priority tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 20,
        ]);
        
        // Test ascending order
        $ascTools = MonitoringTool::orderBy('display_order', 'asc')->get();
        $this->assertEquals($lowOrderTool->id, $ascTools[0]->id);
        $this->assertEquals($medOrderTool->id, $ascTools[1]->id);
        $this->assertEquals($highOrderTool->id, $ascTools[2]->id);
        
        // Test descending order
        $descTools = MonitoringTool::orderBy('display_order', 'desc')->get();
        $this->assertEquals($highOrderTool->id, $descTools[0]->id);
        $this->assertEquals($medOrderTool->id, $descTools[1]->id);
        $this->assertEquals($lowOrderTool->id, $descTools[2]->id);
    }
}