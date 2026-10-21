# Social login

Requires PHP 8.2.35+ (or approved patch lines 8.3.36+, 8.4.27+, 8.5.12+), maintained Laravel 12 and HybridAuth 3.13. Install with `composer install`, copy `.env.example` to `.env`, configure OAuth credentials and public HTTPS `APP_URL`, then `php artisan key:generate`. Serve only `public/`. All HTTP, console and test entrypoints use `vendor-runtime/`; the tracked `vendor/` is an inactive historical snapshot and must not be loaded or served.

The Google, Facebook and Twitter links and optional `/auth` callbacks retain their original paths. Register `/gauth/auth`, `/fbauth/auth` and `/twitterAuth/auth` with providers or set explicit redirect URI variables. Twitter retains OAuth1.0a through the maintained HybridAuth adapter. Provider scopes, API entitlements and callback registration still require valid provider accounts/configuration.

HybridAuth 3 replaces its removed v2 endpoint API. A session storage adapter retains OAuth state/tokens on the server in Laravel sessions; redirects return through Laravel to persist that state. Logout clears the complete OAuth session. Access tokens are never rendered. Missing credentials return503; declined/invalid state returns400; provider failures return generic502. No app user or database schema is created. Plan a new app key/session cutover from Laravel4; `SESSION_SECURE_COOKIE=false` is only for local HTTP.

Run `composer test` for links, genuine Google/Facebook redirect construction and state checks, real HybridAuth Google and Twitter callbacks through in-memory HTTP transports, missing configuration, logout and session-storage behavior. Tests use no provider credentials or network; actual provider authorization and live account compatibility remain unverified. No VPS, merge or deployment operation is included.
