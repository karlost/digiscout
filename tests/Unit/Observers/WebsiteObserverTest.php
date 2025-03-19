<?php

namespace Tests\Unit\Observers;

use App\Models\MonitoringTool;
use App\Models\Website;
use App\Observers\WebsiteObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Tests\TestCase;

class WebsiteObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear existing tools to avoid constraint violations
        MonitoringTool::query()->delete();
    }

    /**
     * Test that default monitoring tools are assigned when creating a website with no tools specified
     */
    public function test_default_monitoring_tools_are_assigned_when_creating_website_with_no_tools_specified(): void
    {
        // Create two monitoring tools - one default and one not default
        $defaultTool = MonitoringTool::create([
            'name' => 'Default Tool',
            'code' => 'default_tool_' . uniqid(),
            'description' => 'A default monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);

        $nonDefaultTool = MonitoringTool::create([
            'name' => 'Non-Default Tool',
            'code' => 'non_default_tool_' . uniqid(),
            'description' => 'A non-default monitoring tool',
            'default_interval' => 10,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 20,
        ]);

        // Create a website (triggers observer)
        $website = Website::create([
            'name' => 'Test Website',
            'url' => 'https://test.com',
            'description' => 'Test website description',
            'status' => true,
        ]);

        // Verify settings
        $settings = $website->monitoringSettings()->get();
        
        // Should only have the default tool assigned
        $this->assertEquals(1, $settings->count());
        $this->assertEquals($defaultTool->id, $settings->first()->monitoring_tool_id);
        $this->assertTrue($settings->first()->enabled);
        $this->assertEquals($defaultTool->default_interval, $settings->first()->interval);
    }

    /**
     * Test that inactive default tools are not assigned when creating a website
     */
    public function test_inactive_default_tools_are_not_assigned(): void
    {
        // Create an inactive default tool
        $inactiveDefaultTool = MonitoringTool::create([
            'name' => 'Inactive Default Tool',
            'code' => 'inactive_default_' . uniqid(),
            'description' => 'An inactive default monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => false, // inactive
            'is_default' => true,
            'display_order' => 10,
        ]);

        // Create a website (triggers observer)
        $website = Website::create([
            'name' => 'Test Website',
            'url' => 'https://test.com',
            'description' => 'Test website description',
            'status' => true,
        ]);

        // Verify no settings were created
        $settings = $website->monitoringSettings()->get();
        $this->assertEquals(0, $settings->count());
    }

    /**
     * Test that manually selected monitoring tools are assigned instead of defaults
     */
    public function test_manually_selected_tools_are_assigned(): void
    {
        // Create two monitoring tools - one default and one not default
        $defaultTool = MonitoringTool::create([
            'name' => 'Default Tool',
            'code' => 'default_tool_' . uniqid(),
            'description' => 'A default monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);

        $nonDefaultTool = MonitoringTool::create([
            'name' => 'Non-Default Tool',
            'code' => 'non_default_tool_' . uniqid(),
            'description' => 'A non-default monitoring tool',
            'default_interval' => 10,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 20,
        ]);

        // Mock the request with manually selected tools
        $this->instance('request', new Request(['monitoring_tools' => [$nonDefaultTool->id]]));

        // Create a website (triggers observer)
        $website = Website::create([
            'name' => 'Test Website',
            'url' => 'https://test.com',
            'description' => 'Test website description',
            'status' => true,
        ]);

        // Verify settings
        $settings = $website->monitoringSettings()->get();
        
        // Should only have the manually selected tool assigned (not the default)
        $this->assertEquals(1, $settings->count());
        $this->assertEquals($nonDefaultTool->id, $settings->first()->monitoring_tool_id);
        $this->assertTrue($settings->first()->enabled);
        $this->assertEquals($nonDefaultTool->default_interval, $settings->first()->interval);
    }

    /**
     * Test that default values are set correctly for monitoring settings
     */
    public function test_default_values_are_set_correctly(): void
    {
        // Create a default tool with specific default interval
        $defaultTool = MonitoringTool::create([
            'name' => 'Default Tool',
            'code' => 'default_tool_' . uniqid(),
            'description' => 'A default monitoring tool',
            'default_interval' => 15,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);

        // Create a website (triggers observer)
        $website = Website::create([
            'name' => 'Test Website',
            'url' => 'https://test.com',
            'description' => 'Test website description',
            'status' => true,
        ]);

        // Verify settings
        $settings = $website->monitoringSettings()->get();
        
        // Check that all default values are set correctly
        $this->assertEquals(1, $settings->count());
        $setting = $settings->first();
        
        $this->assertEquals($defaultTool->id, $setting->monitoring_tool_id);
        $this->assertTrue($setting->enabled);
        $this->assertEquals(15, $setting->interval); // Default interval from the tool
        $this->assertEquals(0, $setting->threshold); // Default threshold is 0
        $this->assertFalse($setting->notify); // Default notify is false
        $this->assertFalse($setting->notify_discord); // Default notify_discord is false
    }

    /**
     * Test direct observer invocation (bypassing model events)
     */
    public function test_direct_observer_invocation(): void
    {
        // Create a default tool
        $defaultTool = MonitoringTool::create([
            'name' => 'Default Tool',
            'code' => 'default_tool_' . uniqid(),
            'description' => 'A default monitoring tool',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);

        // Create a website but skip the observer
        $website = new Website([
            'name' => 'Test Website',
            'url' => 'https://test.com',
            'description' => 'Test website description',
            'status' => true,
        ]);
        $website->saveQuietly();

        // Verify no settings were created
        $this->assertEquals(0, $website->monitoringSettings()->count());

        // Manually invoke the observer
        $observer = new WebsiteObserver();
        $observer->created($website);

        // Refresh and check settings
        $website->refresh();
        $settings = $website->monitoringSettings()->get();
        
        // Should have the default tool assigned
        $this->assertEquals(1, $settings->count());
        $this->assertEquals($defaultTool->id, $settings->first()->monitoring_tool_id);
    }
    
    /**
     * Test that interval values are properly set during website update
     */
    public function test_interval_values_are_preserved_during_update(): void
    {
        // Create tools with different intervals
        $tool1 = MonitoringTool::create([
            'name' => 'Tool 1',
            'code' => 'tool1_' . uniqid(),
            'description' => 'First monitoring tool',
            'default_interval' => 10,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);
        
        $tool2 = MonitoringTool::create([
            'name' => 'Tool 2',
            'code' => 'tool2_' . uniqid(),
            'description' => 'Second monitoring tool',
            'default_interval' => 20,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 20,
        ]);

        // Create a website with tool1 (default)
        $website = Website::create([
            'name' => 'Interval Test Website',
            'url' => 'https://interval-test.com',
            'description' => 'Testing interval preservation',
            'status' => true,
        ]);
        
        // Verify tool1 was assigned with correct interval
        $this->assertEquals(1, $website->monitoringSettings()->count());
        $this->assertEquals($tool1->id, $website->monitoringSettings()->first()->monitoring_tool_id);
        $this->assertEquals($tool1->default_interval, $website->monitoringSettings()->first()->interval);
        
        // Now update the website to use both tools
        $this->instance('request', new Request(['monitoring_tools' => [$tool1->id, $tool2->id]]));
        
        // Manually update the interval for the first tool to a custom value
        $website->monitoringSettings()->where('monitoring_tool_id', $tool1->id)->update([
            'interval' => 15 // Custom interval different from default
        ]);
        
        // Execute update via observer
        $observer = new WebsiteObserver();
        $observer->updated($website);
        
        // Refresh the website
        $website->refresh();
        
        // Should have both tools
        $this->assertEquals(2, $website->monitoringSettings()->count());
        
        // Get settings by tool ID
        $settings = $website->monitoringSettings()->get()->keyBy('monitoring_tool_id');
        
        // Verify tool1 interval was preserved
        $this->assertTrue($settings->has($tool1->id), 'Tool 1 setting is missing');
        $this->assertEquals(15, $settings[$tool1->id]->interval, 'Tool 1 interval was not preserved');
        
        // Verify tool2 was added with its default interval
        $this->assertTrue($settings->has($tool2->id), 'Tool 2 setting is missing');
        $this->assertEquals($tool2->default_interval, $settings[$tool2->id]->interval, 'Tool 2 interval is incorrect');
    }
}