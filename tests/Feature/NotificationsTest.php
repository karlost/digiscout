<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\MonitoringFailureNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $admin;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }
    
    public function test_notifications_page_displays_properly(): void
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(backpack_url('notification'));
        
        $response->assertStatus(200);
        $response->assertSee('Notifikace');
        $response->assertSee('Seznam notifikací');
    }
    
    public function test_notification_detail_displays_properly(): void
    {
        $this->actingAs($this->admin);
        
        // Create a notification
        $data = [
            'website_id' => 1,
            'website_name' => 'Test Website',
            'website_url' => 'https://example.com',
            'tool_id' => 1,
            'tool_name' => 'Test Tool',
            'result_id' => 1,
            'message' => 'Test message',
            'check_time' => now()->toIso8601String(),
        ];
        
        $this->admin->notify(new MonitoringFailureNotification($data));
        
        $notification = $this->admin->notifications()->first();
        
        $response = $this->get(backpack_url('notification/' . $notification->id));
        
        $response->assertStatus(200);
        $response->assertSee('Detail notifikace');
        $response->assertSee($data['website_name']);
        $response->assertSee($data['message']);
    }
    
    public function test_mark_notification_as_read(): void
    {
        $this->actingAs($this->admin);
        
        // Create a notification
        $data = [
            'website_id' => 1,
            'website_name' => 'Test Website',
            'website_url' => 'https://example.com',
            'tool_id' => 1,
            'tool_name' => 'Test Tool',
            'result_id' => 1,
            'message' => 'Test message',
            'check_time' => now()->toIso8601String(),
        ];
        
        $this->admin->notify(new MonitoringFailureNotification($data));
        
        $notification = $this->admin->notifications()->first();
        
        $this->assertNull($notification->read_at);
        
        $response = $this->post(backpack_url('notification/mark-as-read/' . $notification->id));
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }
    
    public function test_mark_all_notifications_as_read(): void
    {
        $this->actingAs($this->admin);
        
        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $data = [
                'website_id' => $i,
                'website_name' => "Test Website $i",
                'website_url' => "https://example$i.com",
                'tool_id' => $i,
                'tool_name' => "Test Tool $i",
                'result_id' => $i,
                'message' => "Test message $i",
                'check_time' => now()->toIso8601String(),
            ];
            
            $this->admin->notify(new MonitoringFailureNotification($data));
        }
        
        $this->assertEquals(3, $this->admin->unreadNotifications()->count());
        
        $response = $this->post(backpack_url('notification/mark-all-as-read'));
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertEquals(0, $this->admin->unreadNotifications()->count());
    }
    
    public function test_delete_notification(): void
    {
        $this->actingAs($this->admin);
        
        // Create a notification
        $data = [
            'website_id' => 1,
            'website_name' => 'Test Website',
            'website_url' => 'https://example.com',
            'tool_id' => 1,
            'tool_name' => 'Test Tool',
            'result_id' => 1,
            'message' => 'Test message',
            'check_time' => now()->toIso8601String(),
        ];
        
        $this->admin->notify(new MonitoringFailureNotification($data));
        
        $notification = $this->admin->notifications()->first();
        
        $this->assertEquals(1, $this->admin->notifications()->count());
        
        $response = $this->delete(backpack_url('notification/' . $notification->id));
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertEquals(0, $this->admin->notifications()->count());
    }
}
