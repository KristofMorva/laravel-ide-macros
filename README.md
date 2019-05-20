# Laravel IDE Macros
It is advised to be used with [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper), which generates helper files for your IDE, so it'll be able to highlight and understand some Laravel-specific syntax.
This package provides an additional IDE helper file for Laravel macros with the syntax you are already used to in Laravel IDE Helper.

## Installation
Just require it in your Composer file, and you are good to go:
```
"tutorigo/laravel-ide-macros": "*"
```
If you are using Laravel 5.4 or lower, you must register the `IdeMacrosServiceProvider` manually.

## Configuration
Run the following command to publish the configuration file to `config/ide-macros.php`:
```
php artisan vendor:publish --provider="Tutorigo\LaravelMacroHelper\IdeMacrosServiceProvider"
```

## Usage

### Generate helper file
Run the following command to generate the macro IDE helpers:
```
php artisan ide-helper:macros
```

### Use of non-static macros
Macros can be both static (ie. `Route::sth()`) and non-static (ie. `Request::route()->sth()`). To distinct the two, use the `@instantiated` tag in the PHPDoc of macros, which depend on `$this`, and `@static` for those that doesn't for example:
```php
/**
 * Gets the amount of route parameters (only instantiated)
 *
 * @return array
 * @instantiated
 */
\Illuminate\Routing\Route::macro('parameterCount', function () {
    /** @var \Illuminate\Routing\Route $this */
    return count($this->parameters);
});

// You get the suggestion on Request::route()-> but not on Route::

/**
 * Get the route URL from name (only static)
 *
 * @return string
 * @static
 */
\Illuminate\Routing\Route::macro('getUrlFromName', function ($name) {
    return route($name);
});

// You get the suggestion on Route:: but not on Request::route()->

/**
 * Get after tomorrow (can be both static and instantiated)
 *
 * @return \Illuminate\Support\Carbon
 */
\Illuminate\Support\Carbon::macro('afterTomorrow', function () {
    /** @var \Illuminate\Support\Carbon $this */
    return isset($this) ? $this->copy()->addDays(2) : now()->addDays(2);
});

// You get the suggestion on both Carbon:: and now()->
```
