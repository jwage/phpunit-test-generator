# PHPUnit Test Generator

This PHP tool can generate PHPUnit test classes for your PHP classes.

This tool currently only supports the PSR4 autoloading strategy. If you would like to see it support
other autoloading strategies and application organizational structures, pull requests are welcome.

## Install

```console
$ composer require --dev jwage/phpunit-test-generator
```

You can also download the latest PHAR from the [releases](https://github.com/jwage/phpunit-test-generator/releases) page.

## Generate Test Class

Take a class named `App\Services\MyService` located in `src/Services/MyService.php`:

```php
namespace App\Services;

class MyService
{
    /** @var Dependency */
    private $dependency;

    /** @var int */
    private $value;

    public function __construct(Dependency $dependency, int $value)
    {
        $this->dependency = $dependency;
        $this->value = $value;
    }

    public function getDependency() : Dependency
    {
        return $this->dependency;
    }

    public function getValue() : int
    {
        return $this->value;
    }
}
```

And a dependency to this class named `App\Services\Dependency` located in `src/Services/Dependency.php`:

```php
<?php

namespace App\Services;

class Dependency
{
    public function getSomething() : null
    {
        return null;
    }
}
```

Now you can generate a test class for `MyService` with the following command:

```console
$ php vendor/bin/generate-unit-test "App\Services\MyService"
```

You can also pass a path to a class instead of giving the class name:

```console
$ php vendor/bin/generate-unit-test src/Services/MyService.php
```

A test would be generated at `tests/Services/MyServiceTest.php` that looks like this:

```php
declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\Dependency;
use App\Services\MyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MyServiceTest extends TestCase
{
    /** @var Dependency|MockObject */
    private $dependency;

    /** @var int */
    private $value;

    /** @var MyService */
    private $myService;

    public function testGetDependency() : void
    {
        self::assertInstanceOf(Dependency::class, $this->myService->getDependency());
    }

    public function testGetValue() : void
    {
        self::assertSame(1, $this->myService->getValue());
    }

    protected function setUp() : void
    {
        $this->dependency = $this->createMock(Dependency::class);
        $this->value = 1;

        $this->myService = new MyService(
            $this->dependency,
            $this->value
        );
    }
}
```

Now you have a skeleton unit test and you can fill in the defails of the generated methods.
