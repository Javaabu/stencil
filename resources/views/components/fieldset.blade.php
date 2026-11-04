<fieldset class="stencil-fieldset">
    @if($node->prop('label'))
        <legend class="stencil-legend">{{ $node->prop('label') }}</legend>
    @endif

    {{ $stencil->children($node->children, $node->prop('columns')) }}
</fieldset>
