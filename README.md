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
- **Email verification prompt** — code input page for verifying newly registered users
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
