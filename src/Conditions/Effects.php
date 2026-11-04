<?php

namespace Javaabu\Stencil\Conditions;

enum Effects: string
{
    case VISIBLE = 'visible';
    case HIDDEN = 'hidden';
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';

    /**
     * Whether the effect hides the component as well as disabling it.
     */
    public function hides(): bool
    {
        return in_array($this, [self::VISIBLE, self::HIDDEN]);
    }

    /**
     * Whether the effect applies when the watched field does not match.
     */
    public function isInverse(): bool
    {
        return in_array($this, [self::HIDDEN, self::DISABLED]);
    }
}
