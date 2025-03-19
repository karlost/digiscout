<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\MonitoringResult;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // Get counts
        $websiteCount = Website::where('status', true)->count();
        $toolCount = MonitoringTool::count();
        
        // Get success and failure counts for the last 24 hours
        $successCount = MonitoringResult::where('status', 'success')
            ->where('created_at', '>=', now()->subDay())
            ->count();
            
        $failureCount = MonitoringResult::where('status', 'failure')
            ->where('created_at', '>=', now()->subDay())
            ->count();
            
        // Get recent failures
        $recentFailures = MonitoringResult::where('status', 'failure')
            ->with(['website', 'monitoringTool'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        // Get success rate for each website
        $websiteStats = Website::where('status', true)
            ->withCount(['monitoringResults as total_checks' => function($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            }])
            ->withCount(['monitoringResults as success_checks' => function($query) {
                $query->where('created_at', '>=', now()->subDays(7))
                      ->where('status', 'success');
            }])
            ->get()
            ->map(function($website) {
                $website->success_rate = $website->total_checks > 0 
                    ? round(($website->success_checks / $website->total_checks) * 100, 2) 
                    : 0;
                return $website;
            });
            
        // Set up the widgets for the dashboard
        $widgets['before_content'] = [
            [
                'type' => 'div',
                'class' => 'row',
                'content' => [
                    [
                        'type' => 'card',
                        'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
                        'class' => 'card text-white bg-primary',
                        'content' => [
                            'header' => 'Active Websites',
                            'body' => $websiteCount,
                        ]
                    ],
                    [
                        'type' => 'card',
                        'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
                        'class' => 'card text-white bg-success',
                        'content' => [
                            'header' => 'Monitoring Tools',
                            'body' => $toolCount,
                        ]
                    ],
                    [
                        'type' => 'card',
                        'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
                        'class' => 'card text-white bg-info',
                        'content' => [
                            'header' => 'Success (24h)',
                            'body' => $successCount,
                        ]
                    ],
                    [
                        'type' => 'card',
                        'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
                        'class' => 'card text-white bg-danger',
                        'content' => [
                            'header' => 'Failures (24h)',
                            'body' => $failureCount,
                        ]
                    ],
                ]
            ]
        ];
            
        // Return the dashboard view with data
        return view('vendor.backpack.base.dashboard', [
            'widgets' => $widgets,
            'websiteCount' => $websiteCount,
            'toolCount' => $toolCount,
            'successCount' => $successCount,
            'failureCount' => $failureCount,
            'recentFailures' => $recentFailures,
            'websiteStats' => $websiteStats
        ]);
    }
}