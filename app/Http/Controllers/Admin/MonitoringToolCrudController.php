<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MonitoringToolRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class MonitoringToolCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MonitoringToolCrudController extends CrudController
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
        CRUD::setModel(\App\Models\MonitoringTool::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/monitoring-tool');
        CRUD::setEntityNameStrings('monitoring tool', 'monitoring tools');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Tool Name');
        CRUD::column('code')->label('Code');
        CRUD::column('default_interval')->label('Default Interval');
        CRUD::column('interval_unit')->label('Interval Unit');
        CRUD::column('is_active')->label('Active')->type('boolean');
        CRUD::column('is_default')->label('Default')->type('boolean');
        CRUD::column('display_order')->label('Display Order');
        CRUD::column('created_at')->type('datetime')->label('Created');
        
        // Set default sort order
        $this->crud->orderBy('display_order');
        
        // Add filters
        CRUD::filter('is_active')
            ->type('dropdown')
            ->label('Active Status')
            ->values([
                1 => 'Active',
                0 => 'Inactive',
            ]);
            
        CRUD::filter('is_default')
            ->type('dropdown')
            ->label('Default Status')
            ->values([
                1 => 'Default',
                0 => 'Not Default',
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
        CRUD::setValidation(MonitoringToolRequest::class);
        
        CRUD::field('name')
            ->label('Tool Name')
            ->type('text');
            
        CRUD::field('code')
            ->label('Tool Code')
            ->type('text')
            ->hint('Unique identifier code for this tool');
            
        CRUD::field('description')
            ->label('Description')
            ->type('textarea');
            
        CRUD::field('default_interval')
            ->label('Default Interval')
            ->type('number')
            ->default(5);
            
        CRUD::field('interval_unit')
            ->label('Interval Unit')
            ->type('select_from_array')
            ->options([
                'second' => 'Second',
                'minute' => 'Minute',
                'hour' => 'Hour'
            ])
            ->default('minute');
            
        CRUD::field('is_active')
            ->label('Active')
            ->type('checkbox')
            ->default(true)
            ->hint('Enable or disable this monitoring tool');
            
        CRUD::field('is_default')
            ->label('Default')
            ->type('checkbox')
            ->default(false)
            ->hint('Should this tool be automatically added to new websites?');
            
        CRUD::field('display_order')
            ->label('Display Order')
            ->type('number')
            ->default(0)
            ->hint('Controls the order in which tools are displayed (lower numbers first)');
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
