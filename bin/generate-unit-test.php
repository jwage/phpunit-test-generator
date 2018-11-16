<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

use JWage\PHPUnitTestGenerator\Command\GenerateTestClassCommand;
use Symfony\Component\Console\Application;
use const PHP_EOL;
use function extension_loaded;
use function file_exists;

(static function () : void {
    $autoloadFiles = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
    ];

    $autoloaderFound = false;

    foreach ($autoloadFiles as $autoloadFile) {
        if (! file_exists($autoloadFile)) {
            continue;
        }

        require_once $autoloadFile;
        $autoloaderFound = true;
    }

    if (! $autoloaderFound) {
        if (extension_loaded('phar') && Phar::running() !== '') {
            echo 'The PHAR was built without dependencies!' . PHP_EOL;
            exit(1);
        }

        echo 'vendor/autoload.php could not be found. Did you run `composer install`?', PHP_EOL;
        exit(1);
    }

    $application = new Application();
    $application->add(new GenerateTestClassCommand());
    $application->run();
})();
