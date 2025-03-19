<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MonitoringResultRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class MonitoringResultCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MonitoringResultCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    // Removed CreateOperation, UpdateOperation, and DeleteOperation for read-only results

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\MonitoringResult::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/monitoring-result');
        CRUD::setEntityNameStrings('monitoring result', 'monitoring results');
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
            ->entity('website')
            ->attribute('name');
            
        CRUD::column('monitoring_tool_id')
            ->label('Monitoring Tool')
            ->type('relationship')
            ->entity('monitoringTool')
            ->attribute('name');
            
        CRUD::column('status')
            ->label('Status')
            ->type('enum')
            ->options([
                'success' => 'Success',
                'failure' => 'Failure'
            ]);
            
        CRUD::column('value')
            ->label('Value')
            ->type('number')
            ->decimals(2);
            
        CRUD::column('check_time')
            ->label('Check Time')
            ->type('datetime');
            
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
            
        CRUD::filter('status')
            ->type('dropdown')
            ->label('Status')
            ->values([
                'success' => 'Success',
                'failure' => 'Failure',
            ]);
            
        CRUD::filter('check_time')
            ->type('date_range')
            ->label('Check Time');
        
        // Set default order for latest results first
        CRUD::orderBy('check_time', 'desc');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(MonitoringResultRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
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
