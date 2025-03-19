{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('/') }}"><i class="la la-dashboard nav-icon"></i> Dashboard</a></li>

<!-- Websites Management -->
<x-backpack::menu-dropdown title="Websites" icon="la la-globe">
    <x-backpack::menu-dropdown-item title="All Websites" icon="la la-list" :link="backpack_url('website')" />
    <x-backpack::menu-dropdown-item title="Add New Website" icon="la la-plus" :link="backpack_url('website/create')" />
</x-backpack::menu-dropdown>

<!-- Monitoring Tools -->
<x-backpack::menu-dropdown title="Monitoring Tools" icon="la la-tools">
    <x-backpack::menu-dropdown-item title="All Tools" icon="la la-list" :link="backpack_url('monitoring-tool')" />
    <x-backpack::menu-dropdown-item title="Add New Tool" icon="la la-plus" :link="backpack_url('monitoring-tool/create')" />
</x-backpack::menu-dropdown>

<!-- Website Settings -->
<x-backpack::menu-item title="Monitoring Settings" icon="la la-cog" :link="backpack_url('website-monitoring-setting')" />

<!-- Results -->
<x-backpack::menu-dropdown title="Results" icon="la la-chart-bar">
    <x-backpack::menu-dropdown-item title="All Results" icon="la la-list" :link="backpack_url('monitoring-result')" />
    <x-backpack::menu-dropdown-item title="Failed Checks" icon="la la-exclamation-triangle" :link="backpack_url('monitoring-result?status=failure')" />
</x-backpack::menu-dropdown>