<?php

namespace Mortezamasumi\FbAuth\Enums;

enum AuthType: string
{
    case User = 'user';
    case Code = 'code';
    case Mobile = 'mobile';
    case Link = 'link';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolveRecord(mixed $data): array
    {
        return match ($this) {
            self::User => ['username' => $data['username']],
            self::Mobile => ['mobile' => $data['mobile']],
            self::Code => ['email' => $data['email']],
            self::Link => ['email' => $data['email']],
        };
    }

    public function unVerifyAttribute(): ?string
    {
        return match ($this) {
            self::Mobile => 'mobile',
            self::Code,
            self::Link => 'email',
            default => null,
        };
    }
}
