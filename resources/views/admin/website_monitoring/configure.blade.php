@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">{{ $title }}</span>
        </h2>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Website Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Name:</label>
                                <div>{{ $website->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">URL:</label>
                                <div><a href="{{ $website->url }}" target="_blank">{{ $website->url }}</a></div>
                            </div>
                        </div>
                    </div>
                    @if($website->description)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold">Description:</label>
                                    <div>{{ $website->description }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form action="{{ backpack_url('website/'.$website->id.'/monitoring/update') }}" method="POST">
        @csrf
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Monitoring Tools Configuration</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 15%">Tool</th>
                                        <th style="width: 45%">Description</th>
                                        <th style="width: 10%">Interval</th>
                                        <th style="width: 10%">Threshold</th>
                                        <th style="width: 15%">Notifications</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monitoringTools as $index => $tool)
                                        @php
                                            $setting = $currentSettings[$tool->id] ?? null;
                                            $enabled = $setting ? $setting->enabled : false;
                                            $interval = $setting ? $setting->interval : $tool->default_interval;
                                            $threshold = $setting ? $setting->threshold : 0;
                                            $notify = $setting ? $setting->notify : false;
                                            $notifyDiscord = $setting ? $setting->notify_discord : false;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="hidden" name="tools[{{ $index }}][id]" value="{{ $tool->id }}">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input monitoring-tool-checkbox" 
                                                        name="tools[{{ $index }}][enabled]" 
                                                        id="tool_{{ $tool->id }}_enabled" 
                                                        value="1" 
                                                        data-tool-id="{{ $tool->id }}"
                                                        {{ $enabled ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <label for="tool_{{ $tool->id }}_enabled" class="form-check-label">
                                                    <strong>{{ $tool->name }}</strong>
                                                </label>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $tool->description }}</span>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" 
                                                        class="form-control tool-setting" 
                                                        name="tools[{{ $index }}][interval]" 
                                                        value="{{ $interval }}" 
                                                        min="1" 
                                                        data-tool-id="{{ $tool->id }}"
                                                        {{ $enabled ? '' : 'disabled' }}>
                                                    <span class="input-group-text">{{ ucfirst($tool->interval_unit) }}s</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                    class="form-control tool-setting" 
                                                    name="tools[{{ $index }}][threshold]" 
                                                    value="{{ $threshold }}" 
                                                    step="0.01" 
                                                    data-tool-id="{{ $tool->id }}"
                                                    {{ $enabled ? '' : 'disabled' }}>
                                            </td>
                                            <td>
                                                <div class="form-check mb-1">
                                                    <input type="checkbox" 
                                                        class="form-check-input tool-setting" 
                                                        name="tools[{{ $index }}][notify]" 
                                                        id="tool_{{ $tool->id }}_notify" 
                                                        value="1" 
                                                        data-tool-id="{{ $tool->id }}"
                                                        {{ $notify ? 'checked' : '' }}
                                                        {{ $enabled ? '' : 'disabled' }}>
                                                    <label for="tool_{{ $tool->id }}_notify" class="form-check-label">
                                                        Email
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" 
                                                        class="form-check-input tool-setting" 
                                                        name="tools[{{ $index }}][notify_discord]" 
                                                        id="tool_{{ $tool->id }}_notify_discord" 
                                                        value="1" 
                                                        data-tool-id="{{ $tool->id }}"
                                                        {{ $notifyDiscord ? 'checked' : '' }}
                                                        {{ $enabled ? '' : 'disabled' }}>
                                                    <label for="tool_{{ $tool->id }}_notify_discord" class="form-check-label">
                                                        Discord
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ backpack_url('website/'.$website->id.'/show') }}" class="btn btn-secondary">
                                <i class="la la-arrow-left"></i> Back to website
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-save"></i> Save configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('after_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle inputs when tool checkbox is clicked
        document.querySelectorAll('.monitoring-tool-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const toolId = this.getAttribute('data-tool-id');
                const enabled = this.checked;
                
                document.querySelectorAll(`.tool-setting[data-tool-id="${toolId}"]`).forEach(function(input) {
                    input.disabled = !enabled;
                });
            });
        });
    });
</script>
@endsection