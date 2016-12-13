# Server setup for Edward Lynx

## Requirements
* MySQL 5.7
* Apache
* PHP >= 5.6.4
* PHP extensions: OpenSSL, PDO, Mbstring, Mcrypt, Tokenizer, XML

## Project Setup
1. Create a database in MYSQL named `ths`.
2. Update database credentials `DB_HOST`, `DB_USERNAME` and `DB_PASSWORD` in the `.env` file.
3. Set web server's document/web root to the `public/` directory.
4. Migrate and seed the database: (in the root directory)
    php artisan migrate
    php artisan db:seed


## Additional docs:
* Installation: https://laravel.com/docs/5.3/installation
* Dev box setup: https://laravel.com/docs/5.3/homestead