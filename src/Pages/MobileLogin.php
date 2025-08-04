<?php

namespace Mortezamasumi\FbAuth\Pages;

use Filament\Auth\Pages\Login as PagesLogin;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class MobileLogin extends PagesLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getMobileFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getMobileFormComponent(): TextInput
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
        return [
            'mobile' => $data['mobile'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.mobile' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
