<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Monitoring\PingService;
use App\Services\Monitoring\HttpStatusService;
use App\Services\Monitoring\DnsCheckService;
use App\Services\Monitoring\LoadTimeService;
use App\Services\Monitoring\SslCertificateService;
use App\Models\MonitoringTool;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register individual monitoring services
        $this->app->singleton(PingService::class);
        $this->app->singleton(HttpStatusService::class);
        $this->app->singleton(DnsCheckService::class);
        $this->app->singleton(LoadTimeService::class);
        $this->app->singleton(SslCertificateService::class);
        
        // Register a monitoring service manager
        $this->app->singleton('monitoring.services', function ($app) {
            return [
                $app->make(PingService::class),
                $app->make(HttpStatusService::class),
                $app->make(DnsCheckService::class),
                $app->make(LoadTimeService::class),
                $app->make(SslCertificateService::class),
            ];
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure all monitoring tools exist in the database
        $this->registerMonitoringTools();
    }
    
    /**
     * Register all monitoring tools in the database
     */
    protected function registerMonitoringTools(): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            // Only run when not in tests to avoid issues in testing
            try {
                $services = app('monitoring.services');
                
                foreach ($services as $service) {
                    MonitoringTool::updateOrCreate(
                        ['code' => $service->getCode()],
                        [
                            'name' => $service->getName(),
                            'description' => $service->getDescription(),
                            'default_interval' => 5, // Default to 5
                            'interval_unit' => 'minute' // Default to minutes
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Ignore exceptions during database setup (might not have tables yet)
            }
        }
    }
}