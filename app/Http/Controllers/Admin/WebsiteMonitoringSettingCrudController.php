<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\WebsiteMonitoringSettingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class WebsiteMonitoringSettingCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class WebsiteMonitoringSettingCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\WebsiteMonitoringSetting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/website-monitoring-setting');
        CRUD::setEntityNameStrings('website monitoring setting', 'website monitoring settings');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('website_id')
            ->label('Website')
            ->type('relationship')
            ->relation_type('BelongsTo')
            ->entity('website')
            ->attribute('name');
            
        CRUD::column('monitoring_tool_id')
            ->label('Monitoring Tool')
            ->type('relationship')
            ->relation_type('BelongsTo')
            ->entity('monitoringTool')
            ->attribute('name');
            
        CRUD::column('interval')
            ->label('Interval');
            
        CRUD::column('enabled')
            ->label('Enabled')
            ->type('boolean');
            
        CRUD::column('notify')
            ->label('Email Notification')
            ->type('boolean');
            
        CRUD::column('notify_discord')
            ->label('Discord Notification')
            ->type('boolean');

        // Add filters
        CRUD::filter('website_id')
            ->type('select2')
            ->label('Website')
            ->attribute('name')
            ->model('App\Models\Website');
            
        CRUD::filter('monitoring_tool_id')
            ->type('select2')
            ->label('Monitoring Tool')
            ->attribute('name')
            ->model('App\Models\MonitoringTool');
            
        CRUD::filter('enabled')
            ->type('dropdown')
            ->label('Status')
            ->values([
                1 => 'Enabled',
                0 => 'Disabled',
            ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(WebsiteMonitoringSettingRequest::class);
        
        CRUD::field('website_id')
            ->label('Website')
            ->type('relationship')
            ->entity('website')
            ->attribute('name')
            ->ajax(false)
            ->options(function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            });
            
        CRUD::field('monitoring_tool_id')
            ->label('Monitoring Tool')
            ->type('relationship')
            ->entity('monitoringTool')
            ->attribute('name')
            ->ajax(false)
            ->options(function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            });
            
        CRUD::field('interval')
            ->label('Check Interval')
            ->type('number')
            ->hint('How often the monitoring should run');
            
        CRUD::field('enabled')
            ->label('Enabled')
            ->type('checkbox')
            ->default(true);
            
        CRUD::field('threshold')
            ->label('Alert Threshold')
            ->type('number')
            ->hint('Set threshold value for alerts (depends on the monitoring tool)');
            
        CRUD::field('notify')
            ->label('Send Email Notifications')
            ->type('checkbox')
            ->default(false);
            
        CRUD::field('notify_discord')
            ->label('Send Discord Notifications')
            ->type('checkbox')
            ->default(false)
            ->hint('Sends alerts to Discord via webhook');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
