# Web Push in raw PHP

## Requirements
- PHP 8.1+ with `openssl`, `mbstring`, `curl` (and `gmp` or `bcmath` recommended), `pdo_sqlite`
- Composer
- HTTPS in production (`http://localhost` is allowed for development)

## Setup
1. `composer install`
2. `php generate-keys.php` and paste the keys into `config.php`
3. Set your contact email in `config.php` (`subject`)
4. `php -S localhost:8000 -t public`
5. Open http://localhost:8000, click "Enable notifications", allow the prompt

<!-- Test Message -->
export OPENSSL_CONF="C:\xampp\apache\conf\openssl.cnf"
`php send.php "Hi" "It works"`

## How it works
1. Browser registers `sw.js` and calls `pushManager.subscribe()` with your VAPID public key.
2. The subscription (endpoint + keys) is POSTed to `subscribe.php` and stored in SQLite.
3. `send.php` loads subscriptions, encrypts a payload per device with minishlink/web-push,
   and sends it to each browser vendor's push service.
4. The push service wakes `sw.js`, which shows the notification.

## Production notes
- Serve over HTTPS; point your web root at `public/` only (keep `config.php`, `data/`, `vendor/` outside it).
- Store the VAPID private key in an environment variable.
- Never regenerate VAPID keys after users subscribe.
- Run `send.php` logic from a queue or cron job rather than inside a web request.
- Add auth to `subscribe.php` and tie `user_id` to your logged-in user.
- iOS requires Safari 16.4+ and the site added to the Home Screen with a web app manifest.
- Expired subscriptions (404/410) are deleted automatically by `send.php`.
