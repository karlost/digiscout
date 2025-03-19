<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NotificationController;

Route::get('/', function () {
    return view('welcome');
});

// Admin notification routes
Route::group([
    'prefix' => config('backpack.base.route_prefix'),
    'middleware' => ['web', 'backpack.auth'],
], function () {
    // Notification routes
    Route::get('notification', [NotificationController::class, 'index']);
    Route::get('notification/{id}', [NotificationController::class, 'show']);
    Route::post('notification/mark-as-read/{id}', [NotificationController::class, 'markAsRead']);
    Route::post('notification/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notification/{id}', [NotificationController::class, 'destroy']);
    
    // Website monitoring configuration routes
    Route::get('website/{websiteId}/monitoring/configure', [\App\Http\Controllers\Admin\WebsiteMonitoringController::class, 'configure']);
    Route::post('website/{websiteId}/monitoring/update', [\App\Http\Controllers\Admin\WebsiteMonitoringController::class, 'update']);
});
