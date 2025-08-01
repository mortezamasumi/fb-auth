<?php

namespace Mortezamasumi\FbAuth\Pages;

use Filament\Auth\Pages\EditProfile as PagesEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Enums\GenderEnum;

class EditProfile extends PagesEditProfile
{
    protected Width|string|null $maxContentWidth = '3xl';

    public static function formComponents(bool $isRegister = false): array
    {
        return [
            FileUpload::make('avatar')
                ->hiddenLabel()
                ->avatar()
                ->disk('public')
                ->directory('avatars')
                ->visibility('public')
                ->maxSize(config('fb-auth.max_avatar_size', 200))
                ->columnSpanFull()
                ->alignCenter()
                ->hidden($isRegister),
            TextInput::make('first_name')
                ->label(__('fb-auth::fb-auth.form.first_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label(__('fb-auth::fb-auth.form.last_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('nid')
                ->label(fn () => (__(config('fb-auth.use_passport_number_on_nid')
                    ? 'fb-auth::fb-auth.form.nid_pass'
                    : 'fb-auth::fb-auth.form.nid')))
                ->required(config('fb-auth.nid_required'))
                ->regex(fn () => (__(config('fb-auth.use_passport_number_on_nid')
                    ? '/^(?:\d{10}|[A-Za-z].*\d{5,})$/'
                    : '/^\d{10}$/')))
                ->maxLength(255)
                // ->rule('iran_nid') ?????
                ->toEN(),
            TextInput::make('profile.father_name')
                ->label(__('fb-auth::fb-auth.form.profile.father_name'))
                ->maxLength(255),
            Select::make('gender')
                ->label(__('fb-auth::fb-auth.form.gender'))
                ->required(config('fb-auth.gender_required'))
                ->options(GenderEnum::class),
            DatePicker::make('birth_date')
                ->label(__('fb-auth::fb-auth.form.birth_date'))
                ->maxDate(now()->endOfDay())
                ->required(config('fb-auth.birth_date_required'))
                ->jDate(),
            TextInput::make('mobile')
                ->label(__('fb-auth::fb-auth.form.mobile'))
                ->required(config('app.auth_type') === AuthType::Mobile)
                ->tel()
                ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
                ->maxLength(30)
                ->toEN(),
            TextInput::make('email')
                ->label(__('filament-panels::auth/pages/register.form.email.label'))
                ->required(config('app.auth_type') === AuthType::Link || config('app.auth_type') === AuthType::Code)
                ->rules(['email'])
                ->extraAttributes(['dir' => 'ltr'])
                ->maxLength(255)
                ->toEN(),
            TextInput::make('username')
                ->label(__('fb-auth::fb-auth.form.username'))
                ->required(config('app.auth_type') === AuthType::User)
                ->maxLength(255),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::formComponents())
            ->columns(3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (config('fb-auth.mobile_required')) {
            // mobile code
        } elseif (config('fb-auth.email_required')) {
            if (config('fb-auth.email_link_verification')) {
                // email link
                if (Filament::hasEmailChangeVerification() && array_key_exists('email', $data)) {
                    $this->sendEmailChangeVerification($record, $data['email']);

                    unset($data['email']);
                }
            } else {
                // email code
            }
        }

        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): ?string
    {
        return Filament::getCurrentPanel()->getLoginUrl();
    }

    // public function save(): void
    // {
    //     parent::save();

    //     $this
    //         ->redirect(
    //             Filament::getCurrentPanel()->getLoginUrl(),
    //             navigate: FilamentView::hasSpaMode() && URL::is(Filament::getCurrentPanel()->getLoginUrl())
    //         );
    // }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('fb-auth::fb-auth.notification.title');
    }
}
