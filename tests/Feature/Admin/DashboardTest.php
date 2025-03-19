<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\MonitoringResult;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
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
        
        // Create some test data
        Website::factory()->count(3)->create(['status' => true]);
        MonitoringTool::factory()->count(2)->create();
        
        // Create some monitoring results
        $website = Website::first();
        $tool = MonitoringTool::first();
        
        // Success result
        MonitoringResult::factory()->create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id,
            'status' => 'success',
            'value' => 42.5,
            'check_time' => now()->subHours(1),
            'additional_data' => ['message' => 'Everything is fine']
        ]);
        
        // Failure result
        MonitoringResult::factory()->create([
            'website_id' => $website->id,
            'monitoring_tool_id' => $tool->id,
            'status' => 'failure',
            'value' => 0,
            'check_time' => now()->subMinutes(30),
            'additional_data' => ['message' => 'Connection timed out']
        ]);
    }
    
    public function test_admin_dashboard_loads_successfully()
    {
        // Login as admin first
        $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);
        
        // Visit the dashboard page
        $response = $this->actingAs($this->admin, 'backpack')
            ->get('/admin');
        
        // Assert the page loads successfully
        $response->assertSuccessful();
        
        // Make sure we don't get a PHP error in the output
        $this->assertStringNotContainsString(
            'Undefined variable $websiteCount',
            $response->getContent()
        );
        
        $this->assertStringNotContainsString(
            'Undefined variable $toolCount',
            $response->getContent()
        );
        
        $this->assertStringNotContainsString(
            'Undefined variable $recentFailures',
            $response->getContent()
        );
        
        $this->assertStringNotContainsString(
            'Undefined variable $websiteStats',
            $response->getContent()
        );
        
        // Assert we can find certain strings that should be in the dashboard
        $this->assertStringContainsString('Active Websites', $response->getContent());
        $this->assertStringContainsString('Monitoring Tools', $response->getContent());
        $this->assertStringContainsString('Success (24h)', $response->getContent());
        $this->assertStringContainsString('Failures (24h)', $response->getContent());
    }
    
    /**
     * Test that the UI dashboard view can render even without variables
     */
    public function test_ui_dashboard_view_handles_missing_variables()
    {
        // We need to be logged in for this test since Backpack uses the authenticated user
        $this->actingAs($this->admin, 'backpack');
        
        // Render the UI dashboard view directly without explicitly passing dashboard variables
        $content = view('vendor.backpack.ui.dashboard')->render();
        
        // Assert that it renders without errors
        $this->assertNotEmpty($content);
        
        // Assert that we don't see any undefined variable errors
        $this->assertStringNotContainsString(
            'Undefined variable',
            $content
        );
    }
    
    public function test_admin_dashboard_displays_widgets_with_correct_values()
    {
        // Login as admin
        $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);
        
        // Visit the dashboard page
        $response = $this->actingAs($this->admin, 'backpack')
            ->get('/admin');
        
        // Check if the controller assigns the expected variables
        $response->assertViewHas('websiteCount');
        $response->assertViewHas('toolCount');
        $response->assertViewHas('successCount');
        $response->assertViewHas('failureCount');
        $response->assertViewHas('recentFailures');
        $response->assertViewHas('websiteStats');
        
        // Additional check to ensure $websiteCount isn't null
        $websiteCount = $response->viewData('websiteCount');
        $this->assertNotNull($websiteCount);
        $this->assertEquals(3, $websiteCount);
    }
    
    public function test_non_admin_cannot_access_dashboard()
    {
        // Create a non-admin user
        $user = User::factory()->create([
            'is_admin' => false
        ]);
        
        // Try to access the dashboard
        $response = $this->actingAs($user, 'backpack')
            ->get('/admin');
        
        // Assert the user is redirected or forbidden
        $this->assertTrue(
            $response->status() == 403 || 
            $response->status() == 302
        );
    }
}