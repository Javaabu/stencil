@php
    $options = $node->prop('options', []);
    $columns = $node->prop('columns');
    $first = array_key_first($options);
    $is_radio = $node->type === 'radio';
@endphp
<x-forms::form-group
    :name="$first === null ? '' : $stencil->optionId($node, $first)"
    :label="$node->prop('label', '')"
    :required="(bool) $node->prop('required')"
    :inline="(bool) $node->prop('inline')"
    :show-label="$node->prop('showLabel', true)"
    class="stencil-options-field"
>
    <div
        class="stencil-options @if($columns) stencil-grid @endif"
        role="{{ $is_radio ? 'radiogroup' : 'group' }}"
        @if($columns) style="{{ $stencil->columnsStyle($columns) }}" @endif
    >
        @foreach($options as $value => $label)
            {{ $stencil->component($node->component, array_filter([
                'name' => $node->name,
                'id' => $stencil->optionId($node, $value),
                'value' => $value,
                'label' => $label,
                'showLabel' => false,
                'showErrors' => false,
                'disabled' => $node->prop('disabled'),
            ], fn ($prop) => $prop !== null)) }}
        @endforeach
    </div>

    @if($node->prop('help'))
        <x-forms::input-help>{{ $node->prop('help') }}</x-forms::input-help>
    @endif

    <x-forms::errors :name="\Illuminate\Support\Str::before($node->name, '[]')" />
</x-forms::form-group>
