{{-- relationships (switchboard; supports both single and multiple: 1-1, 1-n, n-n) --}}
@php
    // Make sure relation_type is defined
    $relationType = $column['relation_type'] ?? null;
    $allowsMultiple = $relationType ? $crud->guessIfFieldHasMultipleFromRelationType($relationType) : false;
    
    // Default to text type if no relation is specified
    if (!$relationType) {
        $column['type'] = 'text';
    } else {
        switch($relationType) {
            case 'HasOne':
            case 'MorphOne': 
                $column['type'] = isset($column['subfields']) ? 'repeatable' : 'text';
            break;
            case 'HasMany':
            case 'HasManyThrough':
            case 'MorphMany':
            case 'BelongsToMany':
            case 'MorphToMany':
                $column['type'] = isset($column['subfields']) ? 'repeatable' : ($allowsMultiple ? 'select_multiple' : 'select');
            break;
            case 'BelongsTo':
            case 'MorphTo':
            case 'HasOneThrough':
                $column['type'] = 'select';
            break;
            default: 
                $column['type'] = 'text';
        }
    }
@endphp

@includeFirst(\Backpack\CRUD\ViewNamespaces::getViewPathsFor('columns', $column['type']))

