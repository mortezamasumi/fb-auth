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
@endphp

<div class="grid gap-y-4 text-center" x-data="otpResend({{ $remainingTime }}, '{{ app()->getLocale() }}')" x-init="init()">
    <template x-if="getTime() <= 0">
        <x-filament::link wire:click="{{ $action->getLivewireClickHandler() }}"
            class="filament-hint-action text-sm font-medium cursor-pointer">
            {{ $action->getLabel() }}
        </x-filament::link>
    </template>
    <template x-if="getTime() > 0">
        <div class="space-x-1 text-sm font-medium opacity-45 flex justify-between" x-text="formatTime(getTime())">
        </div>
    </template>
</div>
