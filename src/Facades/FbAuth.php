<?php

namespace Mortezamasumi\FbAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encodeEmail(string $email)
 * @method static string generateRandomCode()
 * @method static string createCode(\Illuminate\Database\Eloquent\Model $user)
 * @method static array{title: string, body: string} getResetPasswordNotificationKeys()
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
