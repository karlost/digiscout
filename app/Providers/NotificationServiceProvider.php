<?php

namespace App\Providers;

use App\Notifications\Channels\DiscordChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Discord notification channel
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('discord', function ($app) {
                return new DiscordChannel();
            });
        });
    }
}