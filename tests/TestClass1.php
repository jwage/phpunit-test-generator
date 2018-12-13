<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

class TestClass1
{
    /** @var TestDependency */
    private $testDependency;

    /** @var float */
    private $testFloatArgument;

    /** @var int */
    private $testIntegerArgument;

    /** @var string */
    private $testStringArgument;

    /** @var mixed[] */
    private $testArrayArgument;

    /**
     * @param mixed[] $testArrayArgument
     */
    public function __construct(
        TestDependency $testDependency,
        float $testFloatArgument,
        int $testIntegerArgument,
        string $testStringArgument,
        array $testArrayArgument
    ) {
        $this->testDependency      = $testDependency;
        $this->testFloatArgument   = $testFloatArgument;
        $this->testIntegerArgument = $testIntegerArgument;
        $this->testStringArgument  = $testStringArgument;
        $this->testArrayArgument   = $testArrayArgument;
    }

    public function getTestDependency() : TestDependency
    {
        return $this->testDependency;
    }

    public function setTestDependency(TestDependency $testDependency) : void
    {
        $this->testDependency = $testDependency;
    }

    public function getTestFloatArgument() : float
    {
        return $this->testFloatArgument;
    }

    public function getTestIntegerArgument() : int
    {
        return $this->testIntegerArgument;
    }

    public function getTestStringArgument() : string
    {
        return $this->testStringArgument;
    }

    /**
     * @return mixed[]
     */
    public function getTestArrayArgument() : array
    {
        return $this->testArrayArgument;
    }

    public function getTestMethodWithArguments(string $a, float $b, int $c) : void
    {
    }

    public function getSomething() : string
    {
        return 'something';
    }

    public function getTestBoolean() : bool
    {
        return true;
    }

    /**
     * @return mixed[]
     */
    public function getTestArray() : array
    {
        return [];
    }
}
