# FB Auth — Filament Authentication

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mortezamasumi/fb-auth.svg?style=flat-square)](https://packagist.org/packages/mortezamasumi/fb-auth)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mortezamasumi/fb-auth/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/mortezamasumi/fb-auth/actions?query=branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mortezamasumi/fb-auth.svg?style=flat-square)](https://packagist.org/packages/mortezamasumi/fb-auth)
[![License](https://img.shields.io/packagist/l/mortezamasumi/fb-auth.svg?style=flat-square)](LICENSE.md)

A Filament v5 plugin that provides configurable authentication flows for your Laravel admin panel: **magic link**, **email code**, **mobile SMS code**, and **username/password**. It ships custom login, registration, email verification, and password-reset pages — with rate-limited, OTP-based resend actions and bilingual notifications.

---

## Features

- **Four auth flows** — switch with a single config key: `link` (magic link), `code` (email code), `mobile` (SMS code via [fb-sms](https://github.com/mortezamasumi/fb-sms)), and `user` (username/password)
- **OTP login & registration** — dedicated pages with a resendable, expiring code for `code` and `mobile` flows
- **Code-based password reset** — request, resend, and reset flows built on the same OTP mechanism
- **Account verification by code or SMS** — replaces Filament's signed-email-link verification with an OTP prompt page for `code` and `mobile` flows
- **SMS delivery** — mobile codes are sent through the configured fb-sms gateway
- **Rate limiting** — every send/verify action is throttled to prevent abuse
- **Localized UI** — Persian and English translations shipped out of the box

---

## Installation

```bash
composer require mortezamasumi/fb-auth
```

Publish the config file:

```bash
php artisan vendor:publish --tag="fb-auth-config"
```

Optionally publish the views:

```bash
php artisan vendor:publish --tag="fb-auth-views"
```

---

## Configuration

```php
// config/fb-auth.php
return [
    'auth_type' => env('AUTH_TYPE', 'link'),
    'otp_digits' => env('OTP_DIGITS', 4),
    'otp_expiration' => env('OTP_EXPIRATION', 120),
];
```

- `auth_type` — `link` (default), `code`, `mobile`, or `user`
- `otp_digits` — length of the generated verification code (default `4`)
- `otp_expiration` — code lifetime in seconds (default `120`)

---

## Usage

### Register the plugin in a panel

```php
use Mortezamasumi\FbAuth\FbAuthPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FbAuthPlugin::make());
}
```

### Choose an auth flow

Set `AUTH_TYPE` in your `.env`:

| Value | Flow | Login / register | Reset password |
| --- | --- | --- | --- |
| `link` | Magic link | Filament's built-in pages | Filament's built-in pages |
| `code` | Email code | OTP page (code sent by email) | OTP-based reset pages |
| `mobile` | Mobile SMS code | OTP page (code sent by SMS) | OTP-based reset pages |
| `user` | Username / password | Filament's built-in pages | disabled |

### Generate and send codes

```php
use Mortezamasumi\FbAuth\Facades\FbAuth;

$code = FbAuth::createCode($user);        // generates + caches a code for the user
$digits = FbAuth::generateRandomCode();   // random code using otp_digits/otp_expiration

echo FbAuth::encodeEmail('test@example.com'); // masked email, e.g. **st@**mp.**m
```

---

## Account verification

Filament only enforces verification on a panel that calls `->emailVerification()` and when the user model implements `Illuminate\Contracts\Auth\MustVerifyEmail`. Once both are in place, the plugin swaps the stock verification pages for its own, matching the selected flow:

| `AUTH_TYPE` | Verification prompt | Code delivery | Resend action |
| --- | --- | --- | --- |
| `link` | Filament's built-in signed-link page | Laravel's default verify email | stock resend mail |
| `code` | OTP input page | `VerifyCodeNotification` (email with the code inline) | re-sends the email code |
| `mobile` | OTP input page | `VerifyMobileNotification` (SMS via fb-sms) | re-sends the SMS code |
| `user` | verification disabled entirely (`->emailVerification(null)`) | — | — |

### Panel wiring

Enable the features you want; the plugin replaces their pages automatically:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->login()
        ->registration()
        ->passwordReset()
        ->emailVerification()   // required to enforce verification
        ->plugin(FbAuthPlugin::make());
}
```

> **Multi-panel apps:** every panel that enables `->emailVerification()` should also register `FbAuthPlugin`, otherwise unverified users are sent to Filament's stock "click the link in your email" page instead of the OTP prompt.

### Model requirements

- Implement `MustVerifyEmail`.
- Use the `Notifiable` trait — codes are delivered through the notification system.
- For the `mobile` flow: a `mobile` column on the user, [fb-sms](https://github.com/mortezamasumi/fb-sms) installed with an operator configured (`config('fb-sms.operator')`), and `AUTH_TYPE=mobile` in `.env`. The destination defaults to `$user->mobile`; customize it with `routeNotificationFor('sms')` on the model if needed.
- The registration page adapts per flow: `mobile` shows first name / last name / mobile / password, `code` and `link` show first name / last name / email / password, `user` asks for username instead of email.

### How it works

1. On registration (or via the **Resend code** action on the prompt page), `FbAuth::createCode($user)` generates an `otp_digits`-long code and caches it under `otp-{identifier}` for `otp_expiration` seconds. The identifier is the user's `mobile` for the `mobile` flow, `email` otherwise.
2. The user submits the code on the verification prompt. A correct, unexpired call marks the account verified (`markEmailAsVerified()`) and fires the standard `Illuminate\Auth\Events\Verified` event; a wrong or expired code fails validation with a bilingual message and offers resend.
3. Every submit/resend is rate limited (2 attempts per minute). After verification the user is redirected back to the panel URL.
4. If you use fb-user's `User` model, changing the identifier attribute (`mobile` or `email`) resets `email_verified_at`, requiring verification again.

### Customizing the pages

Pass your own page classes to the panel methods as usual — the plugin only fills in defaults for anything you have not overridden:

```php
$panel
    ->emailVerification(MyVerificationPrompt::class)   // extend Mortezamasumi\FbAuth\Pages\VerificationPrompt
    ->passwordReset(MyRequestPasswordReset::class, MyResetPassword::class);
```

The notifications themselves can be swapped by binding `VerifyCodeNotification` / `VerifyMobileNotification` in the container or by publishing and editing the package translations (`fb-auth::fb-auth.*`).

---

## Support policy

| PHP | Laravel | Filament |
| --- | --- | --- |
| 8.3 | 12 | 5.x |

---

## Testing

```bash
composer test
```

---

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please review our [security policy](.github/SECURITY.md) on how to report it.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

---

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
