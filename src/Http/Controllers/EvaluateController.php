<?php

namespace Javaabu\Stencil\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Javaabu\Stencil\Http\LiveToken;
use Javaabu\Stencil\StencilManager;

/**
 * Re-evaluates a live form with the state the browser sends: runs the changed
 * field's afterStateUpdated() callbacks, then renders the form again.
 */
class EvaluateController
{
    /**
     * Request fields that aren't form state.
     *
     * @var array<int, string>
     */
    public const array RESERVED = ['_stencil', '_stencil_changed', '_stencil_old', '_stencil_driver', '_token', '_method'];

    public function __invoke(Request $request, StencilManager $stencil): JsonResponse
    {
        $form = LiveToken::rebuild((string) $request->input('_stencil'));

        abort_if($form === null, 403);

        abort_unless($form->authorize(), 403);

        $form->fillFromInput($request->except(self::RESERVED));

        $changed = $request->input('_stencil_changed');
        $field = is_string($changed) ? $form->getField($changed) : null;

        if ($field?->isLive()) {
            $field->callAfterStateUpdated($request->input('_stencil_old'));
        }

        $driver = $request->input('_stencil_driver', 'json');

        abort_unless(is_string($driver), 422);

        $rendered = $stencil->render($form, $driver);

        return new JsonResponse([
            'changed' => $form->getChangedPaths(),
            'state' => $stencil->render($form, 'json')['state'],
            ...($driver === 'blade' ? ['html' => (string) $rendered] : ['schema' => $rendered]),
        ]);
    }
}
