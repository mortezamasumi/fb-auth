<?php

namespace Mortezamasumi\FbAuth\Pages;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\VerifyCodeNotification;
use Mortezamasumi\FbAuth\Notifications\VerifyMobileNotification;
use Exception;

class Register extends BaseRegister
{
    protected Width|string|null $maxWidth = '2xl';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('fb-auth::fb-auth.form.first_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label(__('fb-auth::fb-auth.form.last_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('mobile')
                    ->label(__('fb-auth::fb-auth.form.mobile'))
                    ->required()
                    ->tel()
                    ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
                    ->maxLength(30)
                    ->toEN()
                    ->visible(config('fb-auth.auth_type') === AuthType::Mobile),
                TextInput::make('email')
                    ->label(__('filament-panels::auth/pages/register.form.email.label'))
                    ->required()
                    ->rules(['email'])
                    ->extraAttributes(['dir' => 'ltr'])
                    ->maxLength(255)
                    ->toEN()
                    ->visible(config('fb-auth.auth_type') === AuthType::Link || config('fb-auth.auth_type') === AuthType::Code),
                TextInput::make('username')
                    ->label(__('fb-auth::fb-auth.form.username'))
                    ->required()
                    ->maxLength(255)
                    ->visible(config('fb-auth.auth_type') === AuthType::User),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (config('fb-auth.auth_type') === AuthType::User) {
            return;
        }

        if (config('fb-auth.auth_type') === AuthType::Link) {
            parent::sendEmailVerificationNotification($user);

            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(
            match (config('fb-auth.auth_type')) {
                AuthType::Code => VerifyCodeNotification::class,
                AuthType::Mobile => VerifyMobileNotification::class,
            },
            [
                'code' => FbAuth::createCode($user),
            ]
        );

        $user->notify($notification);
    }
}
