<?php

namespace Mortezamasumi\FbAuth\Enums;

enum AuthType: string
{
    case User = 'user';
    case Code = 'code';
    case Mobile = 'mobile';
    case Link = 'link';
}
