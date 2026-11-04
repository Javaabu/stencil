<?php

namespace Javaabu\Stencil\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * A field that saves to a relationship (or media) after the model is saved,
 * instead of to one of its attributes.
 */
interface SavesRelationships
{
    public function saveRelationship(Model $record, mixed $state): void;
}
