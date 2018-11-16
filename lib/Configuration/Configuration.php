<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Configuration;

class Configuration
{
    /** @var string */
    private $autoloadingStrategy;

    /** @var string */
    private $sourceNamespace;

    /** @var string */
    private $sourceDir;

    /** @var string */
    private $testsNamespace;

    /** @var string */
    private $testsDir;

    public function __construct(
        string $autoloadingStrategy,
        string $sourceNamespace,
        string $sourceDir,
        string $testsNamespace,
        string $testsDir
    ) {
        $this->autoloadingStrategy = $autoloadingStrategy;
        $this->sourceNamespace     = $sourceNamespace;
        $this->sourceDir           = $sourceDir;
        $this->testsNamespace      = $testsNamespace;
        $this->testsDir            = $testsDir;
    }

    public function getAutoloadingStrategy() : string
    {
        return $this->autoloadingStrategy;
    }

    public function getSourceNamespace() : string
    {
        return $this->sourceNamespace;
    }

    public function getSourceDir() : string
    {
        return $this->sourceDir;
    }

    public function getTestsNamespace() : string
    {
        return $this->testsNamespace;
    }

    public function getTestsDir() : string
    {
        return $this->testsDir;
    }
}
