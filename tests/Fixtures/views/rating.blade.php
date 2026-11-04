{{-- The rating field. $node->name is the input name, $node->props its props. --}}
<x-forms::form-group
    :name="$node->name"
    :label="$node->prop('label', '')"
    :required="(bool) $node->prop('required')"
    :inline="(bool) $node->prop('inline')"
    :show-label="$node->prop('showLabel', true)"
>
    <input
        type="number"
        id="{{ str_replace(['[', ']'], ['-', ''], $node->name) }}"
        name="{{ $node->name }}"
        value="{{ $stencil->value($node) }}"
        min="1"
        max="{{ $node->prop('max') }}"
        @disabled($node->prop('disabled'))
        @required($node->prop('required'))
    >

    @if($node->prop('help'))
        <x-forms::input-help>{{ $node->prop('help') }}</x-forms::input-help>
    @endif

    <x-forms::errors :name="$node->name" />
</x-forms::form-group>
