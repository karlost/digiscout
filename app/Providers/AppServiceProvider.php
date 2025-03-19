<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set the application name for Backpack
        config(['backpack.base.project_name' => 'DigiScout']);
        config(['backpack.base.project_logo' => '<b>Digi</b>Scout']);
        
        // Register observers
        \App\Models\Website::observe(\App\Observers\WebsiteObserver::class);
    }
}
