@php
    use Filament\Support\View\Components\LinkComponent;
    use Mortezamasumi\FbAuth\Enums\AuthType;

    $identifire = match (config('fb-auth.auth_type')) {
        AuthType::Mobile => $this->mobile,
        AuthType::Code => $this->email,
    };

    [$code, $time] = cache()->get('otp-' . $identifire);

    if (!$time) {
        $time = now()->subSeconds(99999);
    }

    $elapsedTime = (int) $time->diffInSeconds(now());

    $remainingTime = config('fb-auth.otp_expiration') - $elapsedTime;

    if ($remainingTime <= 0) {
        $remainingTime = 0;
    }

    $locale = app()->getLocale();
@endphp

<div class="otp-resend-wrapper" x-data="otpResend({{ $remainingTime }}, '{{ $locale }}')" x-init="init()">
    <template x-if="getTime() <= 0">
        <x-filament::link wire:click="{{ $action->getLivewireClickHandler() }}" class="filament-hint-action  otp-resend">
            {{ $action->getLabel() }}
        </x-filament::link>
    </template>
    <template x-if="getTime() > 0">
        <div class="otp-counter" x-text="formatTime(getTime())">
        </div>
    </template>
</div>
