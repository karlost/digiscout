<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toDiscord')) {
            return;
        }

        // Get the Discord webhook URL from config or environment
        $webhookUrl = config('services.discord.webhook_url');
        
        if (empty($webhookUrl)) {
            Log::warning('Discord webhook URL is not configured.');
            return;
        }

        // Get the notification data
        $data = $notification->toDiscord($notifiable);
        
        // Send to Discord
        try {
            $response = Http::post($webhookUrl, $data);
            
            if (!$response->successful()) {
                Log::error('Discord notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Discord notification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}