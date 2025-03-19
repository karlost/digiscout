<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    // Define our custom dashboard as the homepage
    Route::get('/', 'DashboardController@dashboard');
    
    // CRUD routes
    Route::crud('website', 'WebsiteCrudController');
    Route::crud('monitoring-tool', 'MonitoringToolCrudController');
    Route::crud('website-monitoring-setting', 'WebsiteMonitoringSettingCrudController');
    Route::crud('monitoring-result', 'MonitoringResultCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
