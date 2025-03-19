<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock backpack auth keys and functions for testing
        if (!app()->bound('backpack.auth')) {
            app()->bind('backpack.auth', function ($app) {
                return auth();
            });
        }
        
        if (!function_exists('backpack_auth')) {
            function backpack_auth() {
                return auth();
            }
        }
        
        if (!function_exists('backpack_user')) {
            function backpack_user() {
                return auth()->user();
            }
        }
    }
}
