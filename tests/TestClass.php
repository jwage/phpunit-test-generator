<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

class TestClass
{
    /** @var TestDependency */
    private $testDependency;

    /** @var float */
    private $testFloatArgument;

    /** @var int */
    private $testIntegerArgument;

    /** @var string */
    private $testStringArgument;

    public function __construct(
        TestDependency $testDependency,
        float $testFloatArgument,
        int $testIntegerArgument,
        string $testStringArgument
    ) {
        $this->testDependency      = $testDependency;
        $this->testFloatArgument   = $testFloatArgument;
        $this->testIntegerArgument = $testIntegerArgument;
        $this->testStringArgument  = $testStringArgument;
    }

    public function getTestDependency() : TestDependency
    {
        return $this->testDependency;
    }

    public function getTestFloatArgument() : float
    {
        return $this->testFloatArgument;
    }

    public function getTestIntegerArgument() : float
    {
        return $this->testIntegerArgument;
    }

    public function getTestStringArgument() : float
    {
        return $this->testStringArgument;
    }

    public function getSomething() : string
    {
        return 'something';
    }
}
