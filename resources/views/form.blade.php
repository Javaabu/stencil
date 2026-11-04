@if($root->prop('embedded'))
    @model($state)
        {{ $stencil->children($root->children, $root->prop('columns')) }}
    @endmodel
@else
    <x-forms::form
        :id="$root->prop('id')"
        :action="$root->prop('action')"
        :method="$root->prop('method')"
        :files="(bool) $root->prop('files')"
        :model="$state"
        :summary="(bool) $root->prop('summary')"
        :error-bag="$root->prop('errorBag', 'default')"
        class="stencil-form"
        :data-stencil="$root->prop('operation')"
        :data-stencil-live="$live ? json_encode($live) : null"
    >
        {{ $stencil->children($root->children, $root->prop('columns')) }}

        @if($root->prop('submit'))
            <div class="stencil-submit" data-stencil-submit>
                <x-forms::submit>{{ $root->prop('submit') }}</x-forms::submit>
            </div>
        @endif
    </x-forms::form>
@endif
