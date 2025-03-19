<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\WebsiteMonitoringSetting;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the admin user seeder
        $this->call(AdminUserSeeder::class);
        
        // Create admin user if it doesn't exist (legacy code, kept for backward compatibility)
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'is_admin' => true,
            ]
        );
        
        // Create monitoring tools if they don't exist
        $pingTool = MonitoringTool::firstOrCreate(
            ['code' => 'ping'],
            [
                'name' => 'Ping',
                'description' => 'Checks server availability using ICMP ping and measures response time',
                'default_interval' => 5,
                'interval_unit' => 'minute',
            ]
        );
        
        $httpTool = MonitoringTool::firstOrCreate(
            ['code' => 'http_status'],
            [
                'name' => 'HTTP Status',
                'description' => 'Checks HTTP response status code and detects redirects',
                'default_interval' => 5,
                'interval_unit' => 'minute',
            ]
        );
        
        $dnsTool = MonitoringTool::firstOrCreate(
            ['code' => 'dns_check'],
            [
                'name' => 'DNS Check',
                'description' => 'Verifies DNS records and configurations for websites',
                'default_interval' => 60,
                'interval_unit' => 'minute',
            ]
        );
        
        $loadTimeTool = MonitoringTool::firstOrCreate(
            ['code' => 'load_time'],
            [
                'name' => 'Load Time',
                'description' => 'Measures total time to load a website',
                'default_interval' => 10,
                'interval_unit' => 'minute',
            ]
        );
        
        $sslTool = MonitoringTool::firstOrCreate(
            ['code' => 'ssl_certificate'],
            [
                'name' => 'SSL Certificate',
                'description' => 'Checks SSL certificate validity and expiration',
                'default_interval' => 24,
                'interval_unit' => 'hour',
            ]
        );
        
        // Create sample websites if they don't exist
        $website1 = Website::firstOrCreate(
            ['url' => 'https://example.com'],
            [
                'name' => 'Example Website',
                'description' => 'Example website for testing',
                'status' => true,
            ]
        );
        
        $website2 = Website::firstOrCreate(
            ['url' => 'https://google.com'],
            [
                'name' => 'Google',
                'description' => 'Google search engine',
                'status' => true,
            ]
        );
        
        $website3 = Website::firstOrCreate(
            ['url' => 'https://github.com'],
            [
                'name' => 'GitHub',
                'description' => 'Software development platform',
                'status' => true,
            ]
        );
        
        // Create monitoring settings if they don't exist
        WebsiteMonitoringSetting::firstOrCreate(
            [
                'website_id' => $website1->id,
                'monitoring_tool_id' => $pingTool->id,
            ],
            [
                'interval' => 5,
                'enabled' => true,
                'threshold' => 500, // 500ms ping threshold
                'notify' => true,
            ]
        );
        
        WebsiteMonitoringSetting::firstOrCreate(
            [
                'website_id' => $website1->id,
                'monitoring_tool_id' => $httpTool->id,
            ],
            [
                'interval' => 5,
                'enabled' => true,
                'threshold' => null,
                'notify' => true,
            ]
        );
        
        WebsiteMonitoringSetting::firstOrCreate(
            [
                'website_id' => $website1->id,
                'monitoring_tool_id' => $sslTool->id,
            ],
            [
                'interval' => 24,
                'enabled' => true,
                'threshold' => 30, // 30 days before expiration warning
                'notify' => true,
            ]
        );
        
        WebsiteMonitoringSetting::firstOrCreate(
            [
                'website_id' => $website2->id,
                'monitoring_tool_id' => $loadTimeTool->id,
            ],
            [
                'interval' => 10,
                'enabled' => true,
                'threshold' => 2.0, // 2 seconds load time threshold
                'notify' => true,
            ]
        );
        
        WebsiteMonitoringSetting::firstOrCreate(
            [
                'website_id' => $website3->id,
                'monitoring_tool_id' => $dnsTool->id,
            ],
            [
                'interval' => 60,
                'enabled' => true,
                'threshold' => null,
                'notify' => true,
            ]
        );
    }
}
