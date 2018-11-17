<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

use JWage\PHPUnitTestGenerator\Configuration\ConfigurationBuilder;
use JWage\PHPUnitTestGenerator\TestClassGenerator;
use PHPUnit\Framework\TestCase;

class TestClassGeneratorTest extends TestCase
{
    private const EXPECTED_GENERATED_TEST_CLASS = <<<'EOF'
<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

use JWage\PHPUnitTestGenerator\Tests\TestClass;
use JWage\PHPUnitTestGenerator\Tests\TestDependency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TestClassTest extends TestCase
{
    /** @var TestDependency|MockObject */
    private $testDependency;

    /** @var float */
    private $testFloatArgument;

    /** @var int */
    private $testIntegerArgument;

    /** @var string */
    private $testStringArgument;

    /** @var TestClass */
    private $testClass;


    public function testGetTestDependency() : void
    {
        $this->testClass->getTestDependency();
    }

    public function testGetTestFloatArgument() : void
    {
        $this->testClass->getTestFloatArgument();
    }

    public function testGetTestIntegerArgument() : void
    {
        $this->testClass->getTestIntegerArgument();
    }

    public function testGetTestStringArgument() : void
    {
        $this->testClass->getTestStringArgument();
    }

    public function testGetSomething() : void
    {
        $this->testClass->getSomething();
    }

    protected function setUp() : void
    {
        $this->testDependency = $this->createMock(TestDependency::class);
        $this->testFloatArgument = 1.0;
        $this->testIntegerArgument = 1;
        $this->testStringArgument = '';
        $this->testClass = new TestClass(
            $this->testDependency,
            $this->testFloatArgument,
            $this->testIntegerArgument,
            $this->testStringArgument
        );
    }
}

EOF;

    public function testGenerate() : void
    {
        $configuration = (new ConfigurationBuilder())->build();

        $testClassGenerator = new TestClassGenerator($configuration);

        $generatedTestClass = $testClassGenerator->generate(TestClass::class);

        self::assertSame(self::EXPECTED_GENERATED_TEST_CLASS, $generatedTestClass->getCode());
    }
}
