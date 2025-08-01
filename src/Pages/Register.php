<?php

namespace Mortezamasumi\FbAuth\Pages;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Mortezamasumi\FbAuth\Enums\AuthType;

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
                    ->visible(config('app.auth_type') === AuthType::Mobile),
                TextInput::make('email')
                    ->label(__('filament-panels::auth/pages/register.form.email.label'))
                    ->required()
                    ->rules(['email'])
                    ->extraAttributes(['dir' => 'ltr'])
                    ->maxLength(255)
                    ->toEN()
                    ->visible(config('app.auth_type') === AuthType::Link || config('app.auth_type') === AuthType::Link),
                TextInput::make('username')
                    ->label(__('fb-auth::fb-auth.form.username'))
                    ->required()
                    ->maxLength(255)
                    ->visible(config('app.auth_type') === AuthType::User),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
