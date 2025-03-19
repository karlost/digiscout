{{-- select2 from api --}}
@php
    // set default values
    $value = old_empty_or_null($field['name'], false) ?? $field['value'] ?? $field['default'] ?? null;
    $value = is_string($value) ? json_decode($value) : $value;
    $value = is_object($value) || (is_array($value) && is_string(key($value))) ? [$value] : $value;
    $field['placeholder'] ??= trans('backpack::crud.select_entry');
    $field['minimum_input_length'] ??= 2;
    $field['delay'] ??= 500;
    $field['allows_null'] ??= $crud->model::isColumnNullable($field['name']);
    $field['dependencies'] ??= [];
    $field['method'] ??= 'GET';
    $field['include_all_form_fields'] ??= false;
    $field['multiple'] ??= false;
    $field['attributes_to_store'] ??= [$field['attribute'] ?? 'text', 'id'];
    $field['attribute'] ??= current($field['attributes_to_store']);

    $disabled = in_array('disabled', $field['attributes'] ?? []);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <input 
        type="hidden" 
        name="{{ $field['name'] }}"
        value="{{ isset($value) ? json_encode($value) : ''}}"
        @if($disabled) disabled @endif/>

    <select
        style="width: 100%"
        data-init-function="bpFieldInitSelect2FromApiElement"
        data-field-is-inline="{{ var_export($inlineCreate ?? false) }}"
        data-dependencies="{{ json_encode(Arr::wrap($field['dependencies'])) }}"
        data-placeholder="{{ $field['placeholder'] }}"
        data-minimum-input-length="{{ $field['minimum_input_length'] }}"
        data-data-source="{{ $field['data_source'] }}"
        data-method="{{ $field['method'] }}"
        data-allows-null="{{ var_export($field['allows_null']) }}"
        data-include-all-form-fields="{{ var_export($field['include_all_form_fields']) }}"
        data-ajax-delay="{{ $field['delay'] }}"
        data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
        data-attribute="{{ $field['attribute'] }}"
        data-attributes-to-store="{{ json_encode(Arr::wrap($field['attributes_to_store'])) }}"
        bp-field-main-input
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control'])
        @if($field['multiple']) multiple @endif>

        @if ($value)
            @foreach ($value as $item)
                @php
                    $item = is_string($item) ? json_decode($item) : (object) $item;
                @endphp
                <option value="{{ json_encode($item) }}" selected>
                    {{ $item->{$field['attribute']} ?? '' }}
                </option>
            @endforeach
        @endif
    </select>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')


{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}

{{-- FIELD CSS - will be loaded in the after_styles section --}}
@push('crud_fields_styles')
    {{-- include select2 css --}}
    @basset('https://unpkg.com/select2@4.0.13/dist/css/select2.min.css')
    @basset('https://unpkg.com/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css')
@endpush

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
    {{-- include select2 js --}}
    @basset('https://unpkg.com/select2@4.0.13/dist/js/select2.full.min.js')
    @if (app()->getLocale() !== 'en')
        @basset('https://unpkg.com/select2@4.0.13/dist/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js')
    @endif
@endpush

{{-- include field specific select2 js --}}
@push('crud_fields_scripts')
@bassetBlock('backpack/pro/fields/select2-from-api-field.js')
<script>
    function bpFieldInitSelect2FromApiElement($element) {
        const element = $element[0];
        const form = element.closest('form');
        const placeholder = element.dataset.placeholder;
        const minimumInputLength = element.dataset.minimumInputLength;
        const url = element.dataset.dataSource;
        const type = element.dataset.method;
        const includeAllFormFields = element.dataset.includeAllFormFields !== 'false';
        const allowClear = element.dataset.allowsNull;
        const dependencies = JSON.parse(element.dataset.dependencies);
        const delay = element.dataset.ajaxDelay;
        const fieldIsInline = element.dataset.fieldIsInline !== 'false';
        const fieldName = element.dataset.repeatableInputName ?? element.name;
        const multiple = element.multiple;
        const rowNumber = element.dataset.rowNumber !== undefined ? element.dataset.rowNumber - 1 : false;
        const attribute = element.dataset.attribute;
        const attributesToStore = JSON.parse(element.dataset.attributesToStore);

        if (element.classList.contains('select2-hidden-accessible')) {
            return;
        }

        $element.select2({
            theme: 'bootstrap',
            multiple,
            placeholder,
            minimumInputLength,
            allowClear,
            dropdownParent: fieldIsInline ? $('#inline-create-dialog .modal-content') : $(document.body),
            ajax: {
                url,
                type,
                dataType: 'json',
                delay,
                data: function (params) {
                    let data = {
                        q: params.term,
                        page: params.page,
                    };

                    if (includeAllFormFields) {
                        data.form = $(form).serializeArray(),
                        data.triggeredBy = {
                            rowNumber,
                            fieldName,
                        }
                    }

                    return data;
                },
                processResults: function (data, params) {
                    return {
                        results: Object.entries(data.data ?? data).map(([id, text]) => {
                            if(typeof text === 'object') {
                                for (let k in text) {
                                    if(! attributesToStore.includes(k)) {
                                        delete text[k];
                                    }
                                }
                                return { id: JSON.stringify(text), text: text[attribute] };
                            } else {
                                return { id: JSON.stringify({ id, text }), text }
                            }
                        }),
                        pagination: { 
                            more: !!data.next_page_url
                        }
                    }
                },
                cache: true
            }
        });

        $element.on('change', function () {
            let options = [...element.selectedOptions].map(option => JSON.parse(option.value));
            let value = JSON.stringify(multiple ? options : options[0]);
            element.previousElementSibling.value = value;
        });

        // if any dependencies have been declared
        // reset the value when one of those dependencies changes
        dependencies.forEach(dependency => {
            let $input = element.dataset.customSelector
                ? $(form).find(element.dataset.customSelector
                    .replaceAll('%DEPENDENCY%', dependency)
                    .replaceAll('%ROW%', element.dataset.rowNumber))
                : $(form).find(`[name="${dependency}"], [name="${dependency}[]"]`);

            $input.change(function () {
                $($element.find('option:not([value=""])')).remove();
                $element.val(null).trigger('change');
            });
        });
    }
</script>
@endBassetBlock
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
