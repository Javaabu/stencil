<?php

namespace Javaabu\Stencil\Http;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Javaabu\Stencil\Form;
use JsonException;

/**
 * What the server needs to rebuild a live form: its class, record, operation and
 * context, encrypted, and tied to the user it was rendered for.
 */
class LiveToken
{
    public static function issue(Form $form): string
    {
        $lifetime = config('stencil.live.token_lifetime');

        return Crypt::encryptString(json_encode([
            'form' => $form::class,
            'id' => $form->getId(),
            'record' => static::pack($form->getRecord()),
            'model' => $form->getModelClass(),
            'operation' => $form->getOperation(),
            'context' => static::pack($form->context()),
            'user' => Auth::id(),
            'expires' => $lifetime ? now()->addMinutes($lifetime)->getTimestamp() : null,
        ]));
    }

    /**
     * Rebuilds the form, or returns null for a token that is invalid, expired or another user's.
     */
    public static function rebuild(string $token): ?Form
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return null;
        }

        if (! is_a($payload['form'] ?? null, Form::class, true) || $payload['form'] === Form::class) {
            return null;
        }

        if ($payload['expires'] !== null && $payload['expires'] < now()->getTimestamp()) {
            return null;
        }

        if ($payload['user'] !== Auth::id()) {
            return null;
        }

        $record = static::unpack($payload['record']);

        /** @var Form $form */
        $form = $payload['form']::make($payload['id']);

        return $form
            ->with(static::unpack($payload['context']) ?? [])
            ->model($record ?? $payload['model'])
            ->operation($payload['operation']);
    }

    protected static function pack(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return ['__stencil_model' => $value::class, 'key' => $value->getKey(), 'exists' => $value->exists];
        }

        if (is_array($value)) {
            return array_map(static::pack(...), $value);
        }

        if (is_object($value)) {
            return null;
        }

        return $value;
    }

    protected static function unpack(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! isset($value['__stencil_model'])) {
            return array_map(static::unpack(...), $value);
        }

        $class = $value['__stencil_model'];

        if (! is_a($class, Model::class, true)) {
            return null;
        }

        if (! $value['exists']) {
            return new $class;
        }

        return $class::query()->findOrFail($value['key']);
    }
}
