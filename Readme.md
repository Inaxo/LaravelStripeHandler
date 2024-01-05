1. Run command:
```php
composer require inaxo/laravelstripehandler
```
2.  And:
```php
composer require stripe/stripe-php
```
3. Add the following line to the autoload section in the `composer.json` file:
```json
"Inaxo\\LaravelStripeHandler\\": "vendor/inaxo/laravelstripehandler/src/",
```
4. Run the command:
```php
composer dump-autoload
```
5. Add the following line to the providers key array in the config/app.php file:
```php
Inaxo\LaravelStripeHandler\LaravelStripeHandlerServiceProvider::class,
```
6. Run the command again:
```php
composer dump-autoload
```
7. Now, to publish a package config file you need to run the following command:
```php
php artisan vendor:publish --provider="Inaxo\LaravelStripeHandler\LaravelStripeHandlerServiceProvider"
```
8. After publishing the service provider, you need to add this line to the `middleware` array in your `app/Http/Kernel.php`:
```php
\Illuminate\Session\Middleware\StartSession:: class,
```
9. If you've completed all the previous steps, you need to enter data in `resources/LaravelStripeHandler/products.*` (XML by default). After that, you should add additional keys in your .env file:
```
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_HOME_ROUTE=
STRIPE_CURRENCY=
```

Enjoy!
