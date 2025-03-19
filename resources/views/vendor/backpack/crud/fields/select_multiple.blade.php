{{-- select multiple --}}
@php
    if (!isset($field['options'])) {
        $options = $field['model']::all();
    } else {
        $options = call_user_func($field['options'], $field['model']::query());
    }
    $field['allows_null'] = $field['allows_null'] ?? true;

    // Handle the value field
    $debug_error = null;
    try {
        if (isset($field['value']) && is_callable($field['value'])) {
            // If value is a closure/callable, execute it
            $field['value'] = call_user_func($field['value'], $crud ?? null);
        } else {
            // Otherwise, use the normal cascade
            $field['value'] = old_empty_or_null($field['name'], collect()) ?? $field['value'] ?? $field['default'] ?? collect();
        }
    } catch (\Throwable $e) {
        // Capture any errors for debugging
        $debug_error = $e->getMessage();
        // If there's an error, fall back to default or empty array
        $field['value'] = $field['default'] ?? [];
    }

    // Handle default value as a closure if needed
    try {
        if (isset($field['default']) && is_callable($field['default']) && (empty($field['value']) || !is_array($field['value']))) {
            $field['default'] = call_user_func($field['default']);
            $field['value'] = $field['default'];
        }
    } catch (\Throwable $e) {
        if (!$debug_error) { // Only capture if not already set from the value section
            $debug_error = "Default error: " . $e->getMessage();
        }
        // If there's an error, fall back to empty array
        $field['value'] = [];
    }

    // Ensure we have a valid value
    try {
        // Convert value to array if it's not already
        if (!is_array($field['value'])) {
            // Check if it's a collection
            if (is_a($field['value'], \Illuminate\Support\Collection::class)) {
                $field['value'] = $field['value']->toArray();
            } else if (is_null($field['value'])) {
                // If null, use empty array
                $field['value'] = [];
            } else if (is_string($field['value']) || is_numeric($field['value'])) {
                // Convert to array if it's a scalar type
                $field['value'] = [$field['value']];
            } else if (is_object($field['value']) && method_exists($field['value'], 'toArray')) {
                // If it has a toArray method, use it
                $field['value'] = $field['value']->toArray();
            } else {
                // Last resort - empty array
                $debug_error = $debug_error ?? "Value is of unsupported type: " . gettype($field['value']);
                $field['value'] = [];
            }
        }
    } catch (\Throwable $e) {
        $debug_error = $debug_error ?? "Value conversion error: " . $e->getMessage();
        $field['value'] = [];
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    {{-- To make sure a value gets submitted even if the "select multiple" is empty, we need a hidden input --}}
    <input type="hidden" name="{{ $field['name'] }}" value="" @if(in_array('disabled', $field['attributes'] ?? [])) disabled @endif />
    <select
        name="{{ $field['name'] }}[]"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])
        bp-field-main-input
    	multiple>

    	@if (is_array($options) && count($options))
    		@foreach ($options as $key => $option)
				@if(is_array($field['value']) && in_array($key, $field['value']))
					<option value="{{ $key }}" selected>{{ $option }}</option>
				@else
					<option value="{{ $key }}">{{ $option }}</option>
				@endif
    		@endforeach
    	@endif

	</select>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

@if(config('app.debug') && config('app.env') === 'local')
    {{-- Debug information that only shows in local development mode --}}
    <div class="alert alert-{{ isset($debug_error) ? 'warning' : 'info' }} mt-2" 
         style="display: {{ isset($debug_error) ? 'block' : 'none' }};" 
         id="debug-{{ $field['name'] }}">
        <small><strong>Debug info for {{ $field['name'] }}:</strong></small>
        @if(isset($debug_error))
            <small><div class="text-danger mb-2">Error: {{ $debug_error }}</div></small>
        @endif
        <small><pre>Value type: {{ is_array($field['value']) ? 'Array' : gettype($field['value']) }}
Value: {{ is_array($field['value']) ? json_encode($field['value']) : (is_object($field['value']) ? 'Object: ' . get_class($field['value']) : $field['value']) }}</pre></small>
        <button type="button" class="btn btn-xs btn-outline-{{ isset($debug_error) ? 'warning' : 'info' }}" 
                onclick="document.getElementById('debug-{{ $field['name'] }}').style.display = 'none';">Hide</button>
    </div>
    
    @if(isset($debug_error))
    <script>
        // Only in development to help debug
        console.error("{{ $field['name'] }} field error:", {!! json_encode($debug_error) !!});
    </script>
    @endif
@endif