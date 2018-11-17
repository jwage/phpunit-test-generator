<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

class TestClassMetadata
{
    /** @var mixed[] */
    private $useStatements;

    /** @var mixed[] */
    private $properties;

    /** @var mixed[] */
    private $setUpDependencies;

    /** @var mixed[] */
    private $testMethods;

    /**
     * @param mixed[] $useStatements
     * @param mixed[] $properties
     * @param mixed[] $setUpDependencies
     * @param mixed[] $testMethods
     */
    public function __construct(
        array $useStatements,
        array $properties,
        array $setUpDependencies,
        array $testMethods
    ) {
        $this->useStatements     = $useStatements;
        $this->properties        = $properties;
        $this->setUpDependencies = $setUpDependencies;
        $this->testMethods       = $testMethods;
    }

    /**
     * @return mixed[]
     */
    public function getUseStatements() : array
    {
        return $this->useStatements;
    }

    /**
     * @return mixed[]
     */
    public function getProperties() : array
    {
        return $this->properties;
    }

    /**
     * @return mixed[]
     */
    public function getSetUpDependencies() : array
    {
        return $this->setUpDependencies;
    }

    /**
     * @return mixed[]
     */
    public function getTestMethods() : array
    {
        return $this->testMethods;
    }
}
