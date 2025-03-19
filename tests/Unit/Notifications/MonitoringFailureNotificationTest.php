<?php

namespace Tests\Unit\Notifications;

use App\Models\MonitoringResult;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\User;
use App\Notifications\MonitoringFailureNotification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;

class MonitoringFailureNotificationTest extends TestCase
{
    use RefreshDatabase;
    
    protected $result;
    protected $website;
    protected $tool;
    protected $user;
    protected $notification;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->website = Website::factory()->create([
            'name' => 'Test Website',
            'url' => 'https://example.com',
        ]);
        
        $this->tool = MonitoringTool::factory()->create([
            'name' => 'Test Tool',
            'code' => 'test_tool',
        ]);
        
        $this->result = MonitoringResult::factory()->create([
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
            'status' => 'failure',
            'value' => 500,
            'check_time' => now(),
            'additional_data' => [
                'message' => 'Test failure message',
                'details' => 'More detailed error information',
            ],
        ]);
        
        $this->user = User::factory()->create();
        
        $this->notification = new MonitoringFailureNotification($this->result);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_notification_contains_correct_data_for_mail()
    {
        $mailData = $this->notification->toMail($this->user);
        
        $this->assertStringContainsString('Test Website', $mailData->subject);
        $this->assertStringContainsString('Test Website', $mailData->introLines[1]);
        $this->assertStringContainsString('Test Tool', $mailData->introLines[2]);
        $this->assertStringContainsString('Test failure message', $mailData->introLines[4]);
    }
    
    public function test_notification_contains_correct_data_for_array()
    {
        $arrayData = $this->notification->toArray($this->user);
        
        $this->assertEquals($this->website->id, $arrayData['website_id']);
        $this->assertEquals('Test Website', $arrayData['website_name']);
        $this->assertEquals('https://example.com', $arrayData['website_url']);
        $this->assertEquals($this->tool->id, $arrayData['tool_id']);
        $this->assertEquals('Test Tool', $arrayData['tool_name']);
        $this->assertEquals($this->result->id, $arrayData['result_id']);
        $this->assertEquals('Test failure message', $arrayData['message']);
    }
    
    public function test_notification_contains_correct_data_for_discord()
    {
        $discordData = $this->notification->toDiscord($this->user);
        
        $this->assertStringContainsString('Website Monitoring Alert', $discordData['content']);
        $this->assertEquals('Monitoring Alert: Test Website', $discordData['embeds'][0]['title']);
        $this->assertEquals('Test Website (https://example.com)', $discordData['embeds'][0]['fields'][0]['value']);
        $this->assertEquals('Test Tool', $discordData['embeds'][0]['fields'][1]['value']);
        $this->assertEquals('Failed', $discordData['embeds'][0]['fields'][2]['value']);
        $this->assertEquals('Test failure message', $discordData['embeds'][0]['fields'][3]['value']);
    }
    
    public function test_notification_via_channels_includes_email_by_default()
    {
        $channels = $this->notification->via($this->user);
        $this->assertContains('mail', $channels);
    }
    
    public function test_notification_includes_discord_channel_when_enabled()
    {
        // Create a monitoring setting with Discord notifications enabled
        $setting = \App\Models\WebsiteMonitoringSetting::factory()->create([
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
            'notify_discord' => true,
        ]);
        
        $channels = $this->notification->via($this->user);
        $this->assertContains('discord', $channels);
    }
    
    public function test_notification_excludes_discord_channel_when_disabled()
    {
        // Create a monitoring setting with Discord notifications disabled
        $setting = \App\Models\WebsiteMonitoringSetting::factory()->create([
            'website_id' => $this->website->id,
            'monitoring_tool_id' => $this->tool->id,
            'notify_discord' => false,
        ]);
        
        $channels = $this->notification->via($this->user);
        $this->assertNotContains('discord', $channels);
    }
}