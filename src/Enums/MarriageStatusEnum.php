<?php

namespace Mortezamasumi\FbAuth\Enums;

use Filament\Support\Contracts\HasLabel;

enum MarriageStatusEnum: string implements HasLabel
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case UNKNOWN = 'unknown';

    public function getLabel(): ?string
    {
        return __('fb-auth::fb-auth.marriage.'.$this->value);
    }
}
