<?php

namespace App\Notifications;

use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\MonitoringResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Notifications\Messages\SlackMessage;

class MonitoringFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The monitoring result that triggered this notification
     */
    protected MonitoringResult $result;

    /**
     * Create a new notification instance.
     */
    public function __construct(MonitoringResult $result)
    {
        $this->result = $result;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Get notification preferences from the website monitoring settings
        $channels = ['mail'];
        
        $setting = $this->result->website
            ->monitoringSettings()
            ->where('monitoring_tool_id', $this->result->monitoring_tool_id)
            ->first();
            
        if ($setting && $setting->notify_discord) {
            $channels[] = 'discord';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $website = $this->result->website;
        $tool = $this->result->monitoringTool;
        $message = $this->result->additional_data['message'] ?? 'No details available';
        
        return (new MailMessage)
            ->subject("⚠️ Monitoring Alert: {$website->name}")
            ->greeting("Website Monitoring Alert")
            ->line("A monitoring check for {$website->name} has failed.")
            ->line("- Website: {$website->name} ({$website->url})")
            ->line("- Monitoring Tool: {$tool->name}")
            ->line("- Status: Failed")
            ->line("- Details: {$message}")
            ->line("- Time: " . $this->result->check_time->format('Y-m-d H:i:s'))
            ->action('View Details', url('/admin/monitoring-result/' . $this->result->id . '/show'))
            ->line('Please check the website and resolve any issues.');
    }
    
    /**
     * Get the Discord representation of the notification.
     */
    public function toDiscord(object $notifiable)
    {
        $website = $this->result->website;
        $tool = $this->result->monitoringTool;
        $message = $this->result->additional_data['message'] ?? 'No details available';
        $checkTime = $this->result->check_time->format('Y-m-d H:i:s');
        
        return [
            'content' => "⚠️ **Website Monitoring Alert**",
            'embeds' => [
                [
                    'title' => "Monitoring Alert: {$website->name}",
                    'description' => "A monitoring check has failed.",
                    'color' => 15158332, // Red color
                    'fields' => [
                        [
                            'name' => 'Website',
                            'value' => "{$website->name} ({$website->url})",
                            'inline' => true
                        ],
                        [
                            'name' => 'Monitoring Tool',
                            'value' => $tool->name,
                            'inline' => true
                        ],
                        [
                            'name' => 'Status',
                            'value' => 'Failed',
                            'inline' => true
                        ],
                        [
                            'name' => 'Details',
                            'value' => $message,
                            'inline' => false
                        ],
                        [
                            'name' => 'Time',
                            'value' => $checkTime,
                            'inline' => false
                        ]
                    ],
                    'footer' => [
                        'text' => 'DigiScout Monitoring System'
                    ],
                    'timestamp' => now()->toIso8601String()
                ]
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $website = $this->result->website;
        $tool = $this->result->monitoringTool;
        
        return [
            'website_id' => $website->id,
            'website_name' => $website->name,
            'website_url' => $website->url,
            'tool_id' => $tool->id,
            'tool_name' => $tool->name,
            'result_id' => $this->result->id,
            'message' => $this->result->additional_data['message'] ?? 'Check failed',
            'check_time' => $this->result->check_time->toIso8601String(),
        ];
    }
}
