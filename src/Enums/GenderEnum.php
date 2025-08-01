<?php

namespace Mortezamasumi\FbAuth\Enums;

use Filament\Support\Contracts\HasLabel;

enum GenderEnum: string implements HasLabel
{
    case Undefined = 'undefined';
    case Female = 'female';
    case Male = 'male';

    public function getLabel(): ?string
    {
        return __('fb-auth::fb-auth.gender.'.$this->value);
    }

    public function getTitle(): string
    {
        return match ($this) {
            self::Female => __('fb-auth::fb-auth.gender.ms'),
            self::Male => __('fb-auth::fb-auth.gender.mr'),
            default => '',
        };
    }
}
