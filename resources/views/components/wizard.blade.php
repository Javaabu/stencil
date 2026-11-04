@php
    $steps = $node->visibleChildren();
    $start = max(0, min(count($steps), $node->prop('startStep', 1)) - 1);
@endphp
<div class="stencil-wizard mb-4" data-stencil-wizard data-start-step="{{ $start }}">
    <ol class="stencil-wizard-steps">
        @foreach($steps as $index => $step)
            <li class="stencil-wizard-step" data-stencil-wizard-nav="{{ $index }}" @if($index === $start) aria-current="step" @endif>
                <span class="stencil-wizard-number">{{ $index + 1 }}</span>
                <span class="stencil-wizard-text">
                    <span class="stencil-wizard-label">{{ $step->prop('label') }}</span>
                    @if($step->prop('description'))
                        <span class="stencil-wizard-description">{{ $step->prop('description') }}</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ol>

    @foreach($steps as $index => $step)
        <div
            class="stencil-wizard-panel"
            role="group"
            aria-label="{{ __('stencil::strings.step', ['current' => $index + 1, 'total' => count($steps)]) }}"
            data-stencil-wizard-panel="{{ $index }}"
            @if($index !== $start) hidden @endif
        >
            {{ $stencil->node($step) }}
        </div>
    @endforeach

    <div class="stencil-wizard-actions">
        <x-forms::button type="button" color="outline-secondary" data-stencil-wizard-previous :hidden="$start === 0">
            {{ $node->prop('previousLabel') }}
        </x-forms::button>

        <x-forms::button type="button" data-stencil-wizard-next :hidden="$start === count($steps) - 1">
            {{ $node->prop('nextLabel') }}
        </x-forms::button>
    </div>
</div>
