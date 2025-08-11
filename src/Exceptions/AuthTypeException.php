<?php

namespace Mortezamasumi\FbAuth\Exceptions;

use Exception;

class AuthTypeException extends Exception
{
    protected $message = 'Invalid auth type or not specified';
}
