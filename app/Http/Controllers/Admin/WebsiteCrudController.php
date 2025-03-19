<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\WebsiteRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class WebsiteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class WebsiteCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    
    /**
     * Store a newly created resource in the database.
     * 
     * This override allows us to handle the monitoring_tools input
     * properly, fixing the SQL error related to interval field.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        // Execute the parent store method to create the website
        $result = $this->traitStore();
        
        // No additional action needed as WebsiteObserver will handle monitoring tools
        
        return $result;
    }
    
    /**
     * Update the specified resource in the database.
     * 
     * This override allows us to handle the monitoring_tools input
     * properly, fixing the SQL error related to interval field.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        // Execute the parent update method
        $result = $this->traitUpdate();
        
        // No additional action needed as WebsiteObserver will handle monitoring tools
        
        return $result;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Website::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/website');
        CRUD::setEntityNameStrings('website', 'websites');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Website Name');
        CRUD::column('url')->type('link')->label('URL');
        CRUD::column('status')->type('boolean')->label('Active');
        CRUD::column('created_at')->type('datetime')->label('Created');
        
        // Add a filter
        CRUD::filter('status')
            ->type('dropdown')
            ->label('Status')
            ->values([
                1 => 'Active',
                0 => 'Inactive',
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
        CRUD::setValidation(WebsiteRequest::class);
        
        CRUD::field('name')
            ->label('Website Name')
            ->type('text');
            
        CRUD::field('url')
            ->label('Website URL')
            ->type('url');
            
        CRUD::field('description')
            ->label('Description')
            ->type('textarea');
            
        CRUD::field('status')
            ->label('Active')
            ->type('checkbox');
            
        // Add active monitoring tools with our fixed select_multiple field
        $this->crud->addField([
            'name' => 'monitoring_tools',
            'label' => 'Monitoring Tools',
            'type' => 'select_multiple',
            'entity' => 'monitoring_tools',
            'attribute' => 'name',
            'model' => 'App\Models\MonitoringTool',
            'options' => function ($query) {
                return $query->where('is_active', true)
                    ->orderBy('display_order')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')
                    ->toArray();
            },
            // Instead of a closure, use a pre-calculated value for existing entries
            'value' => $this->crud->getCurrentEntry() 
                ? $this->crud->getCurrentEntry()->monitoringSettings()->pluck('monitoring_tool_id')->toArray() 
                : [],
            'hint' => 'Select which monitoring tools should be activated for this website. Default tools are preselected.',
            // Pre-calculate default values instead of using a closure
            'default' => \App\Models\MonitoringTool::where('is_active', true)
                ->where('is_default', true)
                ->pluck('id')
                ->toArray()
        ]);
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
    
    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        // Add a tab for monitoring tools
        $this->crud->addColumn([
            'name' => 'monitoring_tools',
            'label' => 'Monitoring Tools',
            'type' => 'relationship',
            'entity' => 'monitoringSettings', 
            'attribute' => 'id',
            'model' => 'App\Models\WebsiteMonitoringSetting',
            'relation_type' => 'HasMany',
        ])->afterColumn('status');
        
        // Remove the standard columns
        $this->crud->removeColumn('created_at');
        
        // Add a custom button for configuring monitoring
        $this->crud->addButton(
            'line', 
            'configure_monitoring', 
            'view', 
            'vendor.backpack.crud.buttons.configure_monitoring', 
            'beginning'
        );
    }
}
