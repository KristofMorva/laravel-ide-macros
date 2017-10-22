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
Run the following command to generate the macro IDE helpers:
```
php artisan ide-helper:macros
```
