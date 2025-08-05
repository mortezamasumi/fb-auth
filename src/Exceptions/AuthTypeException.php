<?php

namespace Mortezamasumi\FbAuth\Exceptions;

use Exception;

class AuthTypeException extends Exception
{
    protected $message = 'Only and required one of link/code/mobile auth types to be selected';
}
