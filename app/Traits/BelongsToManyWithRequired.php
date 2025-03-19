<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait BelongsToManyWithRequired
{
    /**
     * Hook into the Laravel BelongsToMany class to override the default pivot creation methods
     * This is used to ensure required fields like 'interval' are always set when
     * using the attach, sync, or syncWithoutDetaching methods.
     */
    
    /**
     * Override the BelongsToMany's attach method to handle required pivot fields
     * 
     * @param mixed $id
     * @param array $attributes
     * @param bool $touch
     * @return void
     */
    public function overrideAttach($relation, $id, array $attributes = [], $touch = true)
    {
        if (!is_a($relation, BelongsToMany::class)) {
            return false;
        }
        
        // Get the related model class
        $relatedClass = get_class($relation->getRelated());
        
        // Convert single ID to array
        $ids = $id instanceof Collection ? $id->all() : (array) $id;
        
        // Loop through and handle each ID
        foreach ($ids as $key => $value) {
            $attributes[$key] = $attributes[$key] ?? [];
            
            // Ensure 'interval' is set if not provided
            if (!isset($attributes[$key]['interval'])) {
                // Get the related model instance to access its default_interval
                $relatedModel = $relatedClass::find($value);
                if ($relatedModel && isset($relatedModel->default_interval)) {
                    $attributes[$key]['interval'] = $relatedModel->default_interval ?? 5;
                } else {
                    // Fallback value if no default found
                    $attributes[$key]['interval'] = 5;
                }
            }
            
            // Set additional required fields if not provided
            if (!isset($attributes[$key]['enabled'])) {
                $attributes[$key]['enabled'] = true;
            }
            
            if (!isset($attributes[$key]['threshold'])) {
                $attributes[$key]['threshold'] = 0;
            }
            
            if (!isset($attributes[$key]['notify'])) {
                $attributes[$key]['notify'] = false;
            }
            
            if (!isset($attributes[$key]['notify_discord'])) {
                $attributes[$key]['notify_discord'] = false;
            }
        }
        
        // Now we can safely call the original attach with our enhanced attributes
        return $relation->attach($id, $attributes, $touch);
    }
    
    /**
     * Override the BelongsToMany's sync method to handle required pivot fields
     * 
     * @param mixed $ids
     * @param bool $detaching
     * @return array
     */
    public function overrideSync($relation, $ids, $detaching = true)
    {
        if (!is_a($relation, BelongsToMany::class)) {
            return [[], [], []];
        }
        
        // Get the related model class
        $relatedClass = get_class($relation->getRelated());
        
        // Normalize the provided IDs to array format with attributes
        $ids = $this->normalizeSyncIds($ids);
        
        // Loop through and ensure required fields are set
        foreach ($ids as $id => &$attributes) {
            // Ensure 'interval' is set if not provided
            if (!isset($attributes['interval'])) {
                // Get the related model instance to access its default_interval
                $relatedModel = $relatedClass::find($id);
                if ($relatedModel && isset($relatedModel->default_interval)) {
                    $attributes['interval'] = $relatedModel->default_interval ?? 5;
                } else {
                    // Fallback value if no default found
                    $attributes['interval'] = 5;
                }
            }
            
            // Set additional required fields if not provided
            if (!isset($attributes['enabled'])) {
                $attributes['enabled'] = true;
            }
            
            if (!isset($attributes['threshold'])) {
                $attributes['threshold'] = 0;
            }
            
            if (!isset($attributes['notify'])) {
                $attributes['notify'] = false;
            }
            
            if (!isset($attributes['notify_discord'])) {
                $attributes['notify_discord'] = false;
            }
        }
        
        // Call the original sync with our enhanced attributes
        return $relation->sync($ids, $detaching);
    }
    
    /**
     * Helper method to normalize the IDs to the format expected by the sync method
     * 
     * @param array $ids
     * @return array
     */
    protected function normalizeSyncIds($ids)
    {
        $normalized = [];
        
        foreach ((array) $ids as $id => $attributes) {
            if (is_numeric($id) || is_string($id)) {
                // If indexed by ID with attributes array
                $normalized[$id] = (array) $attributes;
            } else if (is_array($attributes)) {
                // If it's a nested array of ID => attributes
                foreach ($attributes as $key => $value) {
                    $normalized[$key] = (array) $value;
                }
            } else {
                // If it's just an ID as value
                $normalized[$attributes] = [];
            }
        }
        
        return $normalized;
    }
}