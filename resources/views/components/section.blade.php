<x-forms::card class="stencil-section mb-4">
    @if($node->prop('heading'))
        <x-slot:title>{{ $node->prop('heading') }}</x-slot:title>
    @endif

    @if($node->prop('description'))
        <x-slot:subtitle>{{ $node->prop('description') }}</x-slot:subtitle>
    @endif

    {{ $stencil->children($node->children, $node->prop('columns')) }}
</x-forms::card>
