<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\WebsiteMonitoringSetting;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebsiteMonitoringSettingTest extends TestCase
{
    use RefreshDatabase;
    
    protected $admin;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user
        $this->admin = User::factory()->create([
            'is_admin' => true
        ]);
        
        // Create test data
        $website = Website::factory()->create([
            'name' => 'Test Website',
            'url' => 'https://example.com'
        ]);
        
        $tool = MonitoringTool::factory()->create([
            'name' => 'Test Tool',
            'code' => 'test_tool_' . uniqid(),
        ]);
        
        // Create a monitoring setting
        WebsiteMonitoringSetting::create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id,
            'interval' => 5,
            'enabled' => true,
            'threshold' => 500,
            'notify' => true,
            'notify_discord' => false,
        ]);
    }
    
    public function test_admin_can_view_website_monitoring_settings_list()
    {
        // Login as admin first
        $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);
        
        // Visit the monitoring settings list
        $response = $this->actingAs($this->admin, 'backpack')
            ->get('/admin/website-monitoring-setting');
        
        // Assert the page loads successfully without errors
        $response->assertSuccessful();
        
        // Make sure we don't get the specific error about relation_type
        $this->assertStringNotContainsString(
            'Undefined array key "relation_type"',
            $response->getContent()
        );
        
        // Assert page contains needed elements
        $this->assertStringContainsString('Website', $response->getContent());
        $this->assertStringContainsString('Monitoring Tool', $response->getContent());
    }
}