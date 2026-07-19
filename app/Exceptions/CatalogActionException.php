<?php

namespace App\Exceptions;

use RuntimeException;

class CatalogActionException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'action')
    {
        parent::__construct($message);
    }
}
