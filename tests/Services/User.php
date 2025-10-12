<?php

namespace Mortezamasumi\FbAuth\Tests\Services;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mortezamasumi\FbAuth\Enums\AuthType;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $guarded = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getEmailForPasswordReset()
    {
        return match (config('fb-auth.auth_type')) {
            AuthType::Mobile => $this->mobile,
            AuthType::User => $this->username,
            default => $this->email,
        };
    }
}
