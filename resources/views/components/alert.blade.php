@php
    $message = $node->prop('message');
@endphp
{{ $stencil->component('alert', \Illuminate\Support\Arr::except($node->props, ['message']), $message instanceof \Illuminate\Contracts\Support\Htmlable ? $message : e($message)) }}
