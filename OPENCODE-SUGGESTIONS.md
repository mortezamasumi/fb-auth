# OPENCODE-SUGGESTIONS

Status: 78 tests passing (339 assertions) — 0 items pending, 20 items fixed.

Review carried out while bringing fb-auth up to the fb-* package standard
(Pint + PHPStan level 8, CI six gates, README/docs). Findings are recorded
below; implemented items are struck through with a note.

## Bugs

1. ~~`src/FbAuth.php:28` — `str_pad(random_int(...), ...)` passes `int` as `string $string`.~~
   **FIXED** — wrapped in `(string)` cast; covered by existing OTP code tests.
2. ~~`src/FbAuth.php:35` — `$identifire` typo and magic property access on `Model`; the OTP
   cache key was built from `$user->id` / `$user->mobile` / `$user->email` directly.~~
   **FIXED** — renamed to `$identifier`, added `resolveIdentifier()` using
   `getAttribute()`/`getKey()`; covered by code verification tests.
3. ~~`src/Pages/Login.php` — magic `$user->active` / `$user->expiration_date` and
   `$user->expiration_date->isPast()` when `expiration_date` is null (would fatal).~~
   **FIXED** — `getAttribute()` + `instanceof \Illuminate\Support\Carbon` guard;
   covered by the inactive/expired-user login tests.
4. ~~`src/Pages/RequestPasswordReset.php` — `/** @disregard */ Auth::getProvider()->getModel()`
   silenced the type error instead of fixing it; the mobile/email existence closures
   would break for non-Eloquent providers.~~
   **FIXED** — replaced with an `\Illuminate\Auth\EloquentUserProvider` `instanceof`
   check that returns early; covered by existing reset request tests.
5. ~~`src/Pages/RequestPasswordReset.php` — `redirect($notification->url)` read
   `$notification` before it was assigned when the broker never invoked the callback
   (undefined-variable runtime error).~~
   **FIXED** — `$notification` initialized to `null`, guarded before redirect; covered
   by reset request tests.
6. ~~`src/Pages/VerificationPrompt.php:183` — `$this->loginAction` accessed an undefined
   property instead of calling the `loginAction()` method.~~
   **FIXED** — `return $this->loginAction();`; covered by prompt render tests.
7. ~~`src/Pages/*` — `match (config('fb-auth.auth_type'))` matched on `mixed`, so exhaustiveness
   was never checked; `Code`/`Mobile`-only matches silently let `User`/`Link` reach the OTP
   pages and could leave `$title`/`$body` undefined in `getSentNotification()`.~~
   **FIXED** — `AuthType` casts via `/** @var AuthType */`, `default` arms that throw
   `AuthTypeException`, and `[$title, $body]` tuple matches (RequestPasswordReset,
   ResetPassword, VerificationPrompt, Login, Register); covered by mobile flow tests.
8. ~~`src/Pages/ResetPassword.php` — magic `$user->mobile`, `$user->notify()` typed via an
   unknown `Notifiable` class, and `match` on `mixed`.~~
   **FIXED** — `getAttribute('mobile')`, `\Illuminate\Support\Facades\Notification::send()`,
   AuthType casts; covered by reset flow tests.

## API cleanliness / typos

9. ~~`src/FbAuth.php` — `$identifire` typo (public surface of a shipped class).~~
   **FIXED** — see item 2.
10. ~~`src/Notifications/PasswordResetMobileNotification.php`,
    `src/Notifications/VerifyMobileNotification.php` — `public $url;` untyped and
    `via()` missing return type.~~
    **FIXED** — `public ?string $url = null;`, `via(object $notifiable): array` with
    `@return array<string>`.
11. ~~`src/Notifications/PasswordResetCodeNotification.php` — `__construct($token, $code)`
    and `buildMailMessage($url)` untyped.~~
    **FIXED** — typed `string $token` / `string $code`, `buildMailMessage($url): MailMessage`.
12. ~~`src/Facades/FbAuth.php` — `@method createCode(Model $user)` resolved `Model` to
    the facade namespace (`class.notFound`).~~
    **FIXED** — fully qualified `\Illuminate\Database\Eloquent\Model`.
13. ~~`src/FbAuthServiceProvider.php` — `packageRegistered()` / `packageBooted()` missing
    return types; `getAssetPackageName(): ?string` vs FilamentAsset's `string` contract;
    missing `@return array<Asset>`.~~
    **FIXED** — `: void`, `: string`, `@return array<Asset>`.
14. ~~`src/Enums/AuthType.php` — dead `use App\Models\User;` import.~~
    **FIXED** — removed; `resolveRecord()` documented with `@param`/`@return array<string, mixed>`.
15. ~~`src/Testing/TestsFbAuth.php:8` — `@mixin Testable` on a generic class
    (`missingType.generics`).~~
    **FIXED** — `@mixin \Livewire\Features\SupportTesting\Testable<\Livewire\Component>`.

## Meta / release-readiness

16. ~~`.github/workflows/ci.yml` — pest test step commented out; no validate/audit/pint/
    phpstan gates; no `prefer-lowest` run; `actions/checkout@v4`.~~
    **FIXED** — test step uncommented, six quality gates added, `stability` matrix
    dimension `[prefer-stable, prefer-lowest]`, `checkout@v5` (matches fb-essentials/fb-sms).
17. ~~`README.md` — boilerplate placeholder (broken badge workflows `run-tests.yml`,
    `fix-php-code-style-issues.yml`, `echoPhrase` usage, empty config, non-existent
    `fb-auth-migrations` publish tag).~~
    **FIXED** — full rewrite with real badges, config keys, auth-flow table, plugin usage,
    support-policy table, no false publish claims.
18. ~~`CHANGELOG.md` — placeholder `202X-XX-XX` date.~~
    **FIXED** — real dated entries from git history.
19. ~~`composer.json` — `Database\\Factories\\` autoload pointed at a non-existent
    `database/` directory; boilerplate description/keywords; missing `pint`/`analyse`
    scripts and pint/phpstan/larastan dev-deps; `phpstan/extension-installer` in
    allow-plugins.~~
    **FIXED** — autoload removed, description/keywords professional, scripts and
    dev-deps added, allow-plugins only `pestphp/pest-plugin`.
20. ~~`.github/` — missing `CONTRIBUTING.md` and `SECURITY.md`.~~
    **FIXED** — canonical copies added (identical to fb-essentials).

## Tests

- ~~PHPStan baseline: 83 errors (level 8).~~
  **FIXED** — clean; one documented `ignoreErrors` for the `toEN()` macro registered at
  runtime by fb-essentials (same approach as fb-essentials' own config).
- ~~Pint: 15 files failing style.~~
  **FIXED** — clean via `composer pint`.
- No new tests were required: the fixes are internal refactors/type hardening already
  exercised by the existing 72-test / 323-assertion suite (happy path + failure branches
  for expired/mismatched OTP, inactive/expired users, resend throttling).

## Ideas (not started)

- ~~`src/Pages/ResetPassword.php` and `src/Pages/RequestPasswordReset.php` duplicate the
  OTP translation-key logic in `getSentNotification()`; consider extracting a small shared
  helper or moving the keys into one method on `FbAuth`.~~
  **FIXED** — added `FbAuth::getResetPasswordNotificationKeys()` returning
  `array{title: string, body: string}` (exposed on the facade), and both pages now consume
  it; covered by `tests/Tests/FbAuthServiceTest.php`.
- ~~`FbAuthPlugin::register()` uses `switch (config('fb-auth.auth_type')`); switching to an
  exhaustive `match` over `AuthType` would guarantee all four flows are handled.~~
  **FIXED** — `register()` now uses an exhaustive `match` over `AuthType` delegating to
  `configureLinkFlow()` / `configureUserFlow()` / `configureCodeFlow()`; covered by
  `tests/Tests/PluginFlowTest.php` (code, mobile, link, and user flows).
