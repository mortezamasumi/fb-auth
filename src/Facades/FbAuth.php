<?php

namespace Mortezamasumi\FbAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encodeEmail(string $email)
 * @method static string generateRandomCode()
 * @method static string createCode(Model $user)
 *
 * @see \Mortezamasumi\FbAuth\FbAuth
 */
class FbAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Mortezamasumi\FbAuth\FbAuth::class;
    }
}
