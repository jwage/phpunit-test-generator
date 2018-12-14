<?php

declare (strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

use JWage\PHPUnitTestGenerator\TestClassMetadata;
use PHPUnit\Framework\TestCase;

class TestClassMetadataTest extends TestCase
{
    /** @var mixed[] */
    private $useStatements;

    /** @var mixed[] */
    private $properties;

    /** @var mixed[] */
    private $setUpDependencies;

    /** @var mixed[] */
    private $testMethods;

    /** @var TestClassMetadata */
    private $testClassMetadata;

    public function testGetUseStatements() : void
    {
        self::assertSame($this->useStatements, $this->testClassMetadata->getUseStatements());
    }

    public function testGetProperties() : void
    {
        self::assertSame($this->properties, $this->testClassMetadata->getProperties());
    }

    public function testGetSetUpDependencies() : void
    {
        self::assertSame($this->setUpDependencies, $this->testClassMetadata->getSetUpDependencies());
    }

    public function testGetTestMethods() : void
    {
        self::assertSame($this->testMethods, $this->testClassMetadata->getTestMethods());
    }

    protected function setUp() : void
    {
        $this->useStatements     = [self::class];
        $this->properties        = ['property'];
        $this->setUpDependencies = ['setUpDependency'];
        $this->testMethods       = ['testMethod'];

        $this->testClassMetadata = new TestClassMetadata(
            $this->useStatements,
            $this->properties,
            $this->setUpDependencies,
            $this->testMethods
        );
    }
}
