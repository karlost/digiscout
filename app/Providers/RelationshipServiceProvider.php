<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\BelongsToManyWithRequired;

class RelationshipServiceProvider extends ServiceProvider
{
    use BelongsToManyWithRequired;
    
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Extend the BelongsToMany relationship with a safeAttach method
        BelongsToMany::macro('safeAttach', function ($id, array $attributes = [], $touch = true) {
            $provider = app(RelationshipServiceProvider::class);
            return $provider->overrideAttach($this, $id, $attributes, $touch);
        });
        
        // Extend the BelongsToMany relationship with a safeSync method
        BelongsToMany::macro('safeSync', function ($ids, $detaching = true) {
            $provider = app(RelationshipServiceProvider::class);
            return $provider->overrideSync($this, $ids, $detaching);
        });
        
        // Extend the BelongsToMany relationship with a safeSyncWithoutDetaching method
        BelongsToMany::macro('safeSyncWithoutDetaching', function ($ids) {
            $provider = app(RelationshipServiceProvider::class);
            return $provider->overrideSync($this, $ids, false);
        });
    }
}