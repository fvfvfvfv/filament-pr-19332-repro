# Filament #19332 — `crypto.randomUUID` Bug Reproduction

This project reproduces a regression introduced in [filamentphp/filament#19332](https://github.com/filamentphp/filament/pull/19332).

## The Bug

After PR #19332 replaced `uuid-browser` with the native `crypto.randomUUID()` API, the built-in Filament error notification (`errorNotifications`) silently crashes in **non-secure HTTP contexts** (any domain other than `localhost` over plain HTTP).

When a Livewire component throws a 500 error and `APP_DEBUG=false`, the error notification fails with:

```
Uncaught (in promise) TypeError: crypto.randomUUID is not a function
```

The notification never appears. The user gets no feedback that anything went wrong.

**Why:** `crypto.randomUUID()` is only available in [secure contexts](https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts) (HTTPS or `localhost`). `crypto.getRandomValues()` — what the removed `uuid-browser` package used internally — works in all contexts.

## Steps to Reproduce

### Setup

```bash
git clone https://github.com/fvfvfvfv/filament-pr-19332-repro.git
cd filament-pr-19332-repro
composer install
npm install
npm run build
cp .env.example .env   # or use the committed .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
php artisan serve --host=0.0.0.0 --port=8001
```

### Reproduce

> **Important:** You must access the app via your machine's **local network IP**, not `localhost`.
> Browsers treat `localhost` as a secure context, which would not reproduce the bug.

1. Find your local IP address (`ip addr`, `ifconfig`, or `ipconfig` on Windows).
2. Open `http://YOUR_LOCAL_IP:8000/admin` in your browser.
3. Log in with `admin@example.com` / `password`.
4. Click the **"Trigger 500 (reproduce #19332)"** button on the dashboard.
5. Open the browser console — you will see:
   ```
   Uncaught (in promise) TypeError: crypto.randomUUID is not a function
   ```
   No error notification is shown to the user.

### Verify it works on localhost

Open `http://localhost:8000/admin` and click the button and the error notification appears correctly. This confirms the issue is the secure context requirement of `crypto.randomUUID()`.

## Expected Behavior

A danger notification should be displayed to the user regardless of whether the app is served over HTTPS or plain HTTP.

## Environment

- Filament: v4 (post #19332)
- PHP: 8.4
- Browser: tested in Chrome and Firefox
- Context: plain HTTP on a local network IP

## Root Cause

`crypto.randomUUID()` was introduced as a replacement for `uuid-browser` in PR #19332. Unlike `crypto.getRandomValues()`, the `randomUUID()` method is restricted to secure contexts per the [Web Cryptography API spec](https://www.w3.org/TR/WebCryptoAPI/).

This affects any Filament application served over plain HTTP, including local development environments accessed via a local network IP or a `.test` / `.local` hostname that is not `localhost`.
