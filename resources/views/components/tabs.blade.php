@php
    $tabs = array_map(fn ($tab) => ['name' => $tab->key, 'title' => $tab->prop('label')], $node->visibleChildren());
@endphp
<x-forms::tabs :tabs="$tabs" :active="$node->prop('active', '')" class="stencil-tabs mb-4">
    @foreach($node->visibleChildren() as $tab)
        <x-slot :name="\Illuminate\Support\Str::of($tab->key)->camel()->snake()->replace('_', ' ')->camel()->toString()">
            {{ $stencil->node($tab) }}
        </x-slot>
    @endforeach
</x-forms::tabs>
