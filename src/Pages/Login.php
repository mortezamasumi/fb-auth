<?php

namespace Mortezamasumi\FbAuth\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Mortezamasumi\FbAuth\Enums\AuthType;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this
                    ->getMobileFormComponent()
                    ->visible(config('app.auth_type') === AuthType::Mobile),
                $this
                    ->getUsernameFormComponent()
                    ->visible(config('app.auth_type') === AuthType::User),
                $this
                    ->getEmailFormComponent()
                    ->visible(config('app.auth_type') === AuthType::Code || config('app.auth_type') === AuthType::Link),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('fb-auth::fb-auth.form.username'))
            ->required()
            ->maxLength(255);
    }

    protected function getMobileFormComponent(): Component
    {
        return TextInput::make('mobile')
            ->label(__('fb-auth::fb-auth.form.mobile'))
            ->required()
            ->tel()
            ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
            ->maxLength(30)
            ->toEN();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $key = match (config('app.auth_type')) {
            AuthType::Mobile => 'mobile',
            AuthType::Code => 'email',
            AuthType::Link => 'email',
            AuthType::User => 'username',
        };

        return [
            $key => $data[$key],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        $key = match (config('app.auth_type')) {
            AuthType::Mobile => 'mobile',
            AuthType::Code => 'email',
            AuthType::Link => 'email',
            AuthType::User => 'username',
        };

        throw ValidationException::withMessages([
            'data.'.$key => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
