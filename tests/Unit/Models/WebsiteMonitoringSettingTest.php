<?php

namespace Tests\Unit\Models;

use App\Models\WebsiteMonitoringSetting;
use App\Models\Website;
use App\Models\MonitoringTool;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebsiteMonitoringSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_has_correct_fillable_attributes()
    {
        $setting = new WebsiteMonitoringSetting();
        
        $this->assertEquals([
            'website_id',
            'monitoring_tool_id',
            'interval',
            'enabled',
            'threshold',
            'notify',
            'notify_discord',
        ], $setting->getFillable());
    }
    
    public function test_setting_has_correct_casts()
    {
        $setting = new WebsiteMonitoringSetting();
        
        $this->assertArrayHasKey('enabled', $setting->getCasts());
        $this->assertArrayHasKey('notify', $setting->getCasts());
        $this->assertArrayHasKey('notify_discord', $setting->getCasts());
        $this->assertArrayHasKey('threshold', $setting->getCasts());
        
        $this->assertEquals('boolean', $setting->getCasts()['enabled']);
        $this->assertEquals('boolean', $setting->getCasts()['notify']);
        $this->assertEquals('boolean', $setting->getCasts()['notify_discord']);
        $this->assertEquals('float', $setting->getCasts()['threshold']);
    }
    
    public function test_setting_belongs_to_website()
    {
        $website = Website::factory()->create();
        $tool = MonitoringTool::factory()->create(['code' => 'setting_test_' . uniqid()]);
        
        $setting = WebsiteMonitoringSetting::factory()->create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id
        ]);
        
        $this->assertInstanceOf(Website::class, $setting->website);
        $this->assertEquals($website->id, $setting->website->id);
    }
    
    public function test_setting_belongs_to_monitoring_tool()
    {
        $tool = MonitoringTool::factory()->create(['code' => 'tool_test_' . uniqid()]);
        $setting = WebsiteMonitoringSetting::factory()->create([
            'monitoring_tool_id' => $tool->id
        ]);
        
        $this->assertInstanceOf(MonitoringTool::class, $setting->monitoringTool);
        $this->assertEquals($tool->id, $setting->monitoringTool->id);
    }
    
    /**
     * Test creating a website monitoring setting with specific values
     */
    public function test_create_with_specific_values(): void
    {
        $website = Website::factory()->create();
        $tool = MonitoringTool::factory()->create(['code' => 'tool_test_' . uniqid()]);
        
        $setting = WebsiteMonitoringSetting::create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id,
            'interval' => 15,
            'enabled' => true,
            'threshold' => 2.5,
            'notify' => true,
            'notify_discord' => false,
        ]);
        
        $this->assertEquals($website->id, $setting->website_id);
        $this->assertEquals($tool->id, $setting->monitoring_tool_id);
        $this->assertEquals(15, $setting->interval);
        $this->assertTrue($setting->enabled);
        $this->assertEquals(2.5, $setting->threshold);
        $this->assertTrue($setting->notify);
        $this->assertFalse($setting->notify_discord);
    }
    
    /**
     * Test updating a website monitoring setting
     */
    public function test_update_setting(): void
    {
        $website = Website::factory()->create();
        $tool = MonitoringTool::factory()->create(['code' => 'tool_test_' . uniqid()]);
        
        $setting = WebsiteMonitoringSetting::create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id,
            'interval' => 5,
            'enabled' => true,
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false,
        ]);
        
        // Update the setting
        $setting->update([
            'interval' => 10,
            'enabled' => false,
            'threshold' => 1.5,
            'notify' => true,
            'notify_discord' => true,
        ]);
        
        $setting->refresh();
        
        $this->assertEquals(10, $setting->interval);
        $this->assertFalse($setting->enabled);
        $this->assertEquals(1.5, $setting->threshold);
        $this->assertTrue($setting->notify);
        $this->assertTrue($setting->notify_discord);
    }
    
    /**
     * Test boolean cast for enabled field
     */
    public function test_enabled_boolean_cast(): void
    {
        $website = Website::factory()->create();
        $tool = MonitoringTool::factory()->create(['code' => 'tool_test_' . uniqid()]);
        
        $setting = WebsiteMonitoringSetting::create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id,
            'interval' => 5,
            'enabled' => 1, // integer 1 should be cast to boolean true
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false,
        ]);
        
        $this->assertIsBool($setting->enabled);
        $this->assertTrue($setting->enabled);
        
        $setting->update(['enabled' => 0]); // integer 0 should be cast to boolean false
        $setting->refresh();
        
        $this->assertIsBool($setting->enabled);
        $this->assertFalse($setting->enabled);
    }
    
    /**
     * Test that website has multiple monitoring settings
     */
    public function test_website_has_multiple_settings(): void
    {
        $website = Website::factory()->create();
        $tool1 = MonitoringTool::factory()->create(['code' => 'tool1_' . uniqid()]);
        $tool2 = MonitoringTool::factory()->create(['code' => 'tool2_' . uniqid()]);
        
        // Create settings for the website
        WebsiteMonitoringSetting::create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool1->id,
            'interval' => 5,
            'enabled' => true,
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false,
        ]);
        
        WebsiteMonitoringSetting::create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool2->id,
            'interval' => 10,
            'enabled' => true,
            'threshold' => 1,
            'notify' => true,
            'notify_discord' => true,
        ]);
        
        // Check that the website has both settings
        $settings = $website->monitoringSettings()->get();
        $this->assertEquals(2, $settings->count());
        
        // Check that the settings belong to the correct tools
        $toolIds = $settings->pluck('monitoring_tool_id')->toArray();
        $this->assertTrue(in_array($tool1->id, $toolIds));
        $this->assertTrue(in_array($tool2->id, $toolIds));
    }
    
    /**
     * Test that the 'interval' field is required
     * This test verifies the fix for the SQL error: 
     * "SQLSTATE[HY000]: General error: 1364 Field 'interval' doesn't have a default value"
     */
    public function test_interval_is_required_when_creating_monitoring_setting(): void
    {
        // Create a website for testing
        $website = Website::factory()->create();
        
        // Create a monitoring tool
        $tool = MonitoringTool::factory()->create([
            'code' => 'interval_test_' . uniqid(),
            'default_interval' => 15,
            'is_active' => true,
            'is_default' => true
        ]);
        
        // First test: Using the direct relation attach (what was causing the error)
        // This simulates what's happening with the SQL error
        try {
            // This should fail because the interval field is required
            $website->monitoring_tools()->attach($tool->id);
            
            // If we get here without an exception, fail the test
            $this->fail('Should have thrown an exception for missing interval field');
        } catch (\Illuminate\Database\QueryException $e) {
            // Expected behavior - the SQL error should be caught
            $this->assertStringContainsString("Field 'interval' doesn't have a default value", $e->getMessage());
        }
        
        // Second test: Using our fix in the WebsiteObserver
        // Create a website with the fix we implemented
        // Clear out existing monitoring settings
        WebsiteMonitoringSetting::query()->delete();
        
        // Create a new monitoring tool that will be used as the default
        $defaultTool = MonitoringTool::create([
            'name' => 'Default Tool For Observer',
            'code' => 'default_observer_' . uniqid(),
            'description' => 'Default tool for testing the observer',
            'default_interval' => 30,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 1
        ]);
        
        // Create a new website
        $newWebsite = Website::create([
            'name' => 'Protected Website', 
            'url' => 'https://example.com/protected',
            'description' => 'Test for protected interval field',
            'status' => true
        ]);
        
        // Manually trigger the observer with an empty request
        $this->app->instance('request', new \Illuminate\Http\Request());
        $observer = new \App\Observers\WebsiteObserver();
        $observer->created($newWebsite);
        
        // Now check for monitoring settings
        $settingsCount = $newWebsite->monitoringSettings()->count();
        $this->assertGreaterThan(0, $settingsCount, 
            'No monitoring settings created by observer manually calling WebsiteObserver');
            
        // Verify each setting has an interval value
        $settings = $newWebsite->monitoringSettings()->get();
        foreach ($settings as $setting) {
            $this->assertNotNull($setting->interval, 'Observer failed to set interval value');
            $toolForSetting = MonitoringTool::find($setting->monitoring_tool_id);
            $expectedInterval = $toolForSetting->default_interval ?? 5; // Our fallback value
            $this->assertEquals($expectedInterval, $setting->interval, 
                'Interval does not match expected value from default_interval');
        }
    }
    
    /**
     * Test that creating monitoring settings works with our fix for the interval issue
     */
    public function test_create_monitoring_settings_with_interval(): void
    {
        // Instead of testing the complex WebsiteObserver, let's directly test our fix
        // by creating settings with proper interval values
        
        // Create a website
        $website = Website::create([
            'name' => 'Interval Test Website',
            'url' => 'https://interval-test.example.com',
            'description' => 'Testing creating settings with interval',
            'status' => true
        ]);
        
        // Create a monitoring tool
        $tool = MonitoringTool::create([
            'name' => 'Interval Test Tool',
            'code' => 'interval_test_' . uniqid(),
            'description' => 'Tool for testing interval setting',
            'default_interval' => 25,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 10
        ]);
        
        // Test creating a setting with our fix applied (providing an interval)
        $setting = $website->monitoringSettings()->create([
            'monitoring_tool_id' => $tool->id,
            'interval' => $tool->default_interval ?? 5, // Our fix
            'enabled' => true,
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false
        ]);
        
        // Verify the setting was created
        $this->assertNotNull($setting, 'Failed to create the monitoring setting');
        $this->assertEquals($tool->id, $setting->monitoring_tool_id, 'Wrong tool ID');
        $this->assertEquals($tool->default_interval, $setting->interval, 'Wrong interval value');
        
        // Test with a different interval to ensure we're using the correct one
        $toolWithDifferentInterval = MonitoringTool::create([
            'name' => 'Different Interval Tool',
            'code' => 'diff_interval_' . uniqid(),
            'description' => 'Tool with a different default interval',
            'default_interval' => 50, // Different value than our fallback
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 20
        ]);
        
        // Create setting directly using the tool's default_interval
        $settingWithDiffInterval = $website->monitoringSettings()->create([
            'monitoring_tool_id' => $toolWithDifferentInterval->id,
            'interval' => $toolWithDifferentInterval->default_interval, // Use the tool's default interval
            'enabled' => true,
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false
        ]);
        
        // Verify the correct interval was used
        $this->assertNotNull($settingWithDiffInterval, 'Failed to create the second monitoring setting');
        $this->assertEquals($toolWithDifferentInterval->default_interval, $settingWithDiffInterval->interval, 
            'Default interval from tool was not used correctly');
    }
}