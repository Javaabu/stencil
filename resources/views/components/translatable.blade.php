@php
    $inputs = $node->translatable['inputs'];
    $slot_name = fn (array $input) => \Illuminate\Support\Str::of("{$node->key}-{$input['locale']}")->replace('.', '-')->camel()->snake()->replace('_', ' ')->camel()->toString();
@endphp
@if($node->translatable['layout'] === 'stacked' || count($inputs) < 2)
    <div class="stencil-translatable" data-stencil-translatable="stacked">
        @foreach($inputs as $input)
            {{ $stencil->field($node, $stencil->translatableProps($node, $input)) }}
        @endforeach
    </div>
@else
    @php
        $tabs = array_map(fn (array $input) => [
            'name' => str_replace('.', '-', $node->key) . '-' . $input['locale'],
            'title' => $input['language']['native_name'] ?? $input['locale'],
        ], $inputs);
        $invalid = collect($inputs)->first(fn (array $input) => ($errors ?? new \Illuminate\Support\ViewErrorBag)->has($input['statePath']));
    @endphp
    <x-forms::tabs
        :tabs="$tabs"
        :active="str_replace('.', '-', $node->key) . '-' . ($invalid ?? $inputs[0])['locale']"
        class="stencil-translatable mb-4"
        data-stencil-translatable="tabs"
    >
        @foreach($inputs as $input)
            <x-slot :name="$slot_name($input)">
                {{ $stencil->field($node, $stencil->translatableProps($node, $input)) }}
            </x-slot>
        @endforeach
    </x-forms::tabs>
@endif
