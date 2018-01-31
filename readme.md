# Edward Lynx

## Server Requirements
1. MySQL 5.7
2. PHP 5.6 with OpenSSL, PDO, Mbstring, Tokenizer and XML extensions
3. Apache or nginx
4. Composer

## Deployment
1. Clone the repository https://bitbucket.org/ingenuityph/edward-lynx-backend to the server or virtual host.
2. Configure nginx to redirect all requests to the public/index.php folder. See [Laravel Installation Docs](https://laravel.com/docs/5.3).
3. `cd` into the cloned directory.
4. Make sure `storage/` and `public/` are writable by the server and the PHP process.
5. Setup environment variables by copying and editing `.env.example` to `.env`.
4. Install dependencies through composer `composer install`.
5. Run database migrations `php artisan migrate`.
6. Generate OAuth keys using `php artisan passport:install`, taking note of the output as they are the OAuth access keys for API consumers.
(Make sure the private keys `storage/oauth-public.key` and `storage/oauth-private.key` exists.)

## Testing
Run unit tests by invoking `vendor/bin/phpunit`.
