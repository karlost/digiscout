{{-- select2 from ajax multiple --}}
@php
    $connected_entity = new $field['model'];
    $connected_entity_key_name = $connected_entity->getKeyName();
    $old_value = old_empty_or_null($field['name'], false) ??  $field['value'] ?? $field['default'] ?? false;
    $field['placeholder'] = $field['placeholder'] ?? trans('backpack::crud.select_entry');
    $field['attribute'] = $field['attribute'] ?? $connected_entity->identifiableAttribute();
    $field['minimum_input_length'] = $field['minimum_input_length'] ?? 2;

    // by default set ajax query delay to 500ms
    // this is the time we wait before send the query to the search endpoint, after the user as stopped typing.
    $field['delay'] = $field['delay'] ?? 500;
    $field['allows_null'] = $field['allows_null'] ?? $crud->model::isColumnNullable($field['name']);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    {{-- To make sure a value gets submitted even if the "select multiple" is empty, we need a hidden input --}}
    <input type="hidden" name="{{ $field['name'] }}" value="" @if(in_array('disabled', $field['attributes'] ?? [])) disabled @endif />
    <select
        name="{{ $field['name'] }}[]"
        style="width: 100%"
        data-init-function="bpFieldInitSelect2FromAjaxMultipleElement"
        data-field-is-inline="{{var_export($inlineCreate ?? false)}}"
        data-dependencies="{{ isset($field['dependencies'])?json_encode(Arr::wrap($field['dependencies'])): json_encode([]) }}"
        data-placeholder="{{ $field['placeholder'] }}"
        data-minimum-input-length="{{ $field['minimum_input_length'] }}"
        data-data-source="{{ $field['data_source'] }}"
        data-method="{{ $field['method'] ?? 'GET' }}"
        data-allows-null="{{var_export($field['allows_null'])}}"
        data-field-attribute="{{ $field['attribute'] }}"
        data-connected-entity-key-name="{{ $connected_entity_key_name }}"
        data-include-all-form-fields="{{ isset($field['include_all_form_fields']) ? ($field['include_all_form_fields'] ? 'true' : 'false') : 'false' }}"
        data-ajax-delay="{{ $field['delay'] }}"
        data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
        bp-field-main-input
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control'])
        multiple>

        @if ($old_value)
            @foreach ($old_value as $item)
                @if (!is_object($item))
                    @php
                        $item = $connected_entity->find($item);
                    @endphp
                @endif
                <option value="{{ $item->getKey() }}" selected>
                    {{ $item->{$field['attribute']} }}
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
@bassetBlock('backpack/pro/fields/select2-from-ajax-multiple-field-'.app()->getLocale().'.js')
<script>
    function bpFieldInitSelect2FromAjaxMultipleElement(element) {
        var form = element.closest('form');
        var $placeholder = element.attr('data-placeholder');
        var $minimumInputLength = element.attr('data-minimum-input-length');
        var $dataSource = element.attr('data-data-source');
        var $method = element.attr('data-method');
        var $fieldAttribute = element.attr('data-field-attribute');
        var $connectedEntityKeyName = element.attr('data-connected-entity-key-name');
        var $includeAllFormFields = element.attr('data-include-all-form-fields')=='false' ? false : true;
        var $allowClear = element.data('allows-null');
        var $dependencies = JSON.parse(element.attr('data-dependencies'));
        var $ajaxDelay = element.attr('data-ajax-delay');
        var $isFieldInline = element.data('field-is-inline');
        var $fieldCleanName = element.attr('data-repeatable-input-name') ?? element.attr('name');

        if (!$(element).hasClass("select2-hidden-accessible"))
        {
            $(element).select2({
                theme: 'bootstrap',
                multiple: true,
                placeholder: $placeholder,
                minimumInputLength: $minimumInputLength,
                allowClear: $allowClear,
                dropdownParent: $isFieldInline ? $('#inline-create-dialog .modal-content') : $(document.body),
                ajax: {
                    url: $dataSource,
                    type: $method,
                    dataType: 'json',
                    delay: $ajaxDelay,
                    data: function (params) {
                        if ($includeAllFormFields) {
                            return {
                                q: params.term, // search term
                                page: params.page, // pagination
                                form: form.serializeArray(), // all other form inputs
                                triggeredBy: 
                                {
                                    'rowNumber': element.attr('data-row-number') !== 'undefined' ? element.attr('data-row-number')-1 : false, 
                                    'fieldName': $fieldCleanName
                                }
                            };
                        } else {
                            return {
                                q: params.term, // search term
                                page: params.page, // pagination
                            };
                        }
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;

                    //if we have data.data here it means we returned a paginated instance from controller.
                    //otherwise we returned one or more entries unpaginated.
                    let paginate = false;

                    if (data.data) {
                        paginate = data.next_page_url !== null;
                        data = data.data;
                    }

                    return {
                            results: $.map(data, function (item) {
                                var $itemText = processItemText(item, $fieldAttribute);
                                return {
                                    text: $itemText,
                                    id: item[$connectedEntityKeyName],
                                }
                            }),
                           pagination: {
                               more: paginate,
                           }
                        };
                    },
                    cache: true
                },
            });
        }

        $(element).on('CrudField:disable', function(e) {
            let hiddenInput = element.siblings('input[type="hidden"]');
            if(hiddenInput.length) {
                hiddenInput.prop('disabled',true);
            }
            return true;
        });

        $(element).on('CrudField:enable', function(e) {
            let hiddenInput = element.siblings('input[type="hidden"]');
            if(hiddenInput.length) {
                hiddenInput.prop('disabled',false);
            }
            return true;
        });

        // if any dependencies have been declared
        // when one of those dependencies changes value
        // reset the select2 value
        for (var i=0; i < $dependencies.length; i++) {
            var $dependency = $dependencies[i];
            //if element does not have a custom-selector attribute we use the name attribute
            if(typeof element.attr('data-custom-selector') == 'undefined') {
                form.find('[name="'+$dependency+'"], [name="'+$dependency+'[]"]').change(function(el) {
                        $(element.find('option:not([value=""])')).remove();
                        element.val(null).trigger("change");
                });
            }else{
                // we get the row number and custom selector from where element is called
                let rowNumber = element.attr('data-row-number');
                let selector = element.attr('data-custom-selector');

                // replace in the custom selector string the corresponding row and dependency name to match
                selector = selector
                    .replaceAll('%DEPENDENCY%', $dependency)
                    .replaceAll('%ROW%', rowNumber);

                $(selector).change(function (el) {
                    $(element.find('option:not([value=""])')).remove();
                    element.val(null).trigger("change");
                });
            }
        }
    }

    if (typeof processItemText !== 'function') {
        function processItemText(item, $fieldAttribute) {
            var $appLang = '{{ app()->getLocale() }}';
            var $appLangFallback = '{{ Lang::getFallback() }}';
            var $emptyTranslation = '{{ trans("backpack::crud.empty_translations") }}';
            var $itemField = item[$fieldAttribute];

            // try to retreive the item in app language; then fallback language; then first entry; if nothing found empty translation string
            return typeof $itemField === 'object' && $itemField !== null
                ? $itemField[$appLang] ? $itemField[$appLang] : $itemField[$appLangFallback] ? $itemField[$appLangFallback] : Object.values($itemField)[0] ? Object.values($itemField)[0] : $emptyTranslation
                : $itemField;
        }
    }
</script>
@endBassetBlock
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
