<?php

namespace Mortezamasumi\FbAuth\Enums;

enum AuthType: string
{
    case User = 'username';
    case Code = 'email-code';
    case Mobile = 'mobile-code';
    case Link = 'email-link';
}
