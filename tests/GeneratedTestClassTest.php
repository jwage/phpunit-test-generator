<?php

declare (strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

use JWage\PHPUnitTestGenerator\GeneratedTestClass;
use PHPUnit\Framework\TestCase;

class GeneratedTestClassTest extends TestCase
{
    /** @var string */
    private $className;

    /** @var string */
    private $testClassName;

    /** @var string */
    private $code;

    /** @var GeneratedTestClass */
    private $generatedTestClass;

    public function testGetClassName() : void
    {
        self::assertSame($this->className, $this->generatedTestClass->getClassName());
    }

    public function testGetTestClassName() : void
    {
        self::assertSame($this->testClassName, $this->generatedTestClass->getTestClassName());
    }

    public function testGetCode() : void
    {
        self::assertSame($this->code, $this->generatedTestClass->getCode());
    }

    protected function setUp() : void
    {
        $this->className     = 'App\User';
        $this->testClassName = 'App\Tests\User';
        $this->code          = '<?php echo "Hello World";';

        $this->generatedTestClass = new GeneratedTestClass(
            $this->className,
            $this->testClassName,
            $this->code
        );
    }
}
