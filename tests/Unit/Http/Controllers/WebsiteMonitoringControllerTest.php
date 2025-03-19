<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Admin\WebsiteMonitoringController;
use App\Models\MonitoringTool;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteMonitoringSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebsiteMonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Website $website;
    protected MonitoringTool $monitoringTool1;
    protected MonitoringTool $monitoringTool2;
    protected WebsiteMonitoringController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // Clear existing tools before creating new ones, to avoid unique constraint violations
        MonitoringTool::query()->delete();

        // Create monitoring tools
        $this->monitoringTool1 = MonitoringTool::create([
            'name' => 'Ping Test',
            'code' => 'ping_' . uniqid(),
            'description' => 'Tests website accessibility via ping',
            'default_interval' => 5,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 10,
        ]);

        $this->monitoringTool2 = MonitoringTool::create([
            'name' => 'SSL Check',
            'code' => 'ssl_' . uniqid(),
            'description' => 'Tests SSL certificate validity',
            'default_interval' => 60,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 20,
        ]);

        // Create a website
        $this->website = Website::create([
            'name' => 'Test Website',
            'url' => 'https://example.com',
            'description' => 'Test website description',
            'status' => true,
        ]);

        // Create the controller
        $this->controller = new WebsiteMonitoringController();
    }

    /**
     * Test the configure method returns the correct view
     */
    public function test_configure_method_returns_correct_view(): void
    {
        $request = Request::create('/admin/website/1/monitoring/configure', 'GET');
        
        // Mock auth to return admin user
        $this->actingAs($this->admin);
        
        $response = $this->controller->configure($request, $this->website->id);
        
        // Assert that the correct view is returned
        $this->assertEquals('admin.website_monitoring.configure', $response->getName());
        
        // Assert that the view has the required data
        $this->assertArrayHasKey('website', $response->getData());
        $this->assertArrayHasKey('monitoringTools', $response->getData());
        $this->assertArrayHasKey('currentSettings', $response->getData());
        $this->assertArrayHasKey('title', $response->getData());
        $this->assertArrayHasKey('breadcrumbs', $response->getData());
        
        // Assert that only active tools are returned
        $tools = $response->getData()['monitoringTools'];
        $this->assertEquals(2, $tools->count());
        $this->assertEquals($this->monitoringTool1->id, $tools[0]->id);
        $this->assertEquals($this->monitoringTool2->id, $tools[1]->id);
    }
    
    /**
     * Test that inactive tools are not returned by the configure method
     */
    public function test_inactive_tools_not_returned_by_configure_method(): void
    {
        // Make the second tool inactive
        $this->monitoringTool2->update(['is_active' => false]);
        
        $request = Request::create('/admin/website/1/monitoring/configure', 'GET');
        
        // Mock auth to return admin user
        $this->actingAs($this->admin);
        
        $response = $this->controller->configure($request, $this->website->id);
        
        // Assert that only active tools are returned
        $tools = $response->getData()['monitoringTools'];
        $this->assertEquals(1, $tools->count());
        $this->assertEquals($this->monitoringTool1->id, $tools[0]->id);
    }
    
    /**
     * Test that the update method correctly updates monitoring settings
     */
    public function test_update_method_correctly_updates_monitoring_settings(): void
    {
        // Prepare request data
        $requestData = [
            'tools' => [
                [
                    'id' => $this->monitoringTool1->id,
                    'enabled' => true,
                    'interval' => 10,
                    'threshold' => 2.5,
                    'notify' => true,
                    'notify_discord' => true,
                ],
                [
                    'id' => $this->monitoringTool2->id,
                    'enabled' => true,
                    'interval' => 120,
                    'threshold' => 0,
                    'notify' => false,
                    'notify_discord' => true,
                ],
            ],
        ];
        
        $request = new Request($requestData);
        
        // Mock auth to return admin user
        $this->actingAs($this->admin);
        
        // Call the update method
        $response = $this->controller->update($request, $this->website->id);
        
        // Assert redirect back
        $this->assertTrue($response->isRedirect());
        
        // Check database to verify settings were updated
        $settings = $this->website->monitoringSettings()->orderBy('monitoring_tool_id')->get();
        
        // Should have settings for both tools
        $this->assertEquals(2, $settings->count());
        
        // Check first tool settings
        $setting1 = $settings->where('monitoring_tool_id', $this->monitoringTool1->id)->first();
        $this->assertNotNull($setting1);
        $this->assertTrue($setting1->enabled);
        $this->assertEquals(10, $setting1->interval);
        $this->assertEquals(2.5, $setting1->threshold);
        $this->assertTrue($setting1->notify);
        $this->assertTrue($setting1->notify_discord);
        
        // Check second tool settings
        $setting2 = $settings->where('monitoring_tool_id', $this->monitoringTool2->id)->first();
        $this->assertNotNull($setting2);
        $this->assertTrue($setting2->enabled);
        $this->assertEquals(120, $setting2->interval);
        $this->assertEquals(0, $setting2->threshold);
        $this->assertFalse($setting2->notify);
        $this->assertTrue($setting2->notify_discord);
    }
    
    /**
     * Test that the update method correctly disables a tool
     */
    public function test_update_method_correctly_disables_a_tool(): void
    {
        // First create a setting for the tool (enabled)
        WebsiteMonitoringSetting::create([
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->monitoringTool1->id,
            'interval' => 5,
            'enabled' => true,
            'threshold' => 0,
            'notify' => false,
            'notify_discord' => false,
        ]);
        
        // Prepare request data to disable the tool
        $requestData = [
            'tools' => [
                [
                    'id' => $this->monitoringTool1->id,
                    'enabled' => false,
                    'interval' => 5,
                    'threshold' => 0,
                    'notify' => false,
                    'notify_discord' => false,
                ],
            ],
        ];
        
        $request = new Request($requestData);
        
        // Mock auth to return admin user
        $this->actingAs($this->admin);
        
        // Call the update method
        $response = $this->controller->update($request, $this->website->id);
        
        // Assert redirect back
        $this->assertTrue($response->isRedirect());
        
        // Check database to verify the tool was disabled
        $setting = $this->website->monitoringSettings()->where('monitoring_tool_id', $this->monitoringTool1->id)->first();
        $this->assertNotNull($setting);
        $this->assertFalse($setting->enabled);
    }
    
    /**
     * Test validation error handling in the update method
     */
    public function test_update_method_handles_validation_errors(): void
    {
        // Prepare request data with invalid values
        $requestData = [
            'tools' => [
                [
                    'id' => $this->monitoringTool1->id,
                    'enabled' => true,
                    'interval' => -5, // Invalid interval (negative)
                    'threshold' => 0,
                    'notify' => false,
                    'notify_discord' => false,
                ],
            ],
        ];
        
        $request = new Request($requestData);
        
        // Mock auth to return admin user
        $this->actingAs($this->admin);
        
        // Expect validation exception
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        // Call the update method
        $this->controller->update($request, $this->website->id);
    }
    
    /**
     * Test that the update method correctly creates a new setting if one doesn't exist
     */
    public function test_update_method_creates_new_setting_if_not_exists(): void
    {
        // Delete existing settings
        $this->website->monitoringSettings()->delete();
        
        // Verify the website doesn't have any settings (after we've deleted them)
        $this->assertEquals(0, $this->website->monitoringSettings()->count());
        
        // Create a new tool that we know doesn't have settings
        $newTool = MonitoringTool::create([
            'name' => 'New Tool',
            'code' => 'new_tool_' . uniqid(),
            'description' => 'A new tool with no settings',
            'default_interval' => 15,
            'interval_unit' => 'minute',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 30,
        ]);
        
        // Prepare request data
        $requestData = [
            'tools' => [
                [
                    'id' => $newTool->id,
                    'enabled' => true,
                    'interval' => 15,
                    'threshold' => 1.5,
                    'notify' => true,
                    'notify_discord' => false,
                ],
            ],
        ];
        
        $request = new Request($requestData);
        
        // Mock auth to return admin user
        $this->actingAs($this->admin);
        
        // Call the update method
        $response = $this->controller->update($request, $this->website->id);
        
        // Assert redirect back
        $this->assertTrue($response->isRedirect());
        
        // Check database to verify a new setting was created
        $setting = $this->website->monitoringSettings()->where('monitoring_tool_id', $newTool->id)->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->enabled);
        $this->assertEquals(15, $setting->interval);
        $this->assertEquals(1.5, $setting->threshold);
        $this->assertTrue($setting->notify);
        $this->assertFalse($setting->notify_discord);
    }
}