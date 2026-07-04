<?php

namespace App\Exceptions;

interface HasErrorCode
{
    /**
     * A stable, machine-readable error code for clients to branch on.
     *
     * Deliberately decoupled from the class name so it survives refactors and
     * never leaks internal structure. Only exceptions the frontend actually
     * discriminates on need to implement this; everything else just returns a
     * message.
     */
    public function errorCode(): string;
}
