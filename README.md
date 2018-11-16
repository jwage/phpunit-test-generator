# PHPUnit Test Generator

This PHP tool can generate PHPUnit test classes for your PHP classes.

This tool currently only supports the PSR4 autoloading strategy. If you would like to see it support
other autoloading strategies and application organizational structures, pull requests are welcome.

## Install

    composer require --dev jwage/phpunit-test-generator

## Generate Test Class

If you have a class located in `src/Services/MyService.php` and in the namespace `App\Services` you can
generate a test for the class like this:

    ./vendor/bin/generate-test-class "App\Services\MyService"

A test would be generated at `tests/Services/MyServiceTest.php`.
