<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Configuration;

class ConfigurationBuilder
{
    /** @var string */
    private $autoloadingStrategy = '';

    /** @var string */
    private $sourceNamespace = '';

    /** @var string */
    private $sourceDir = '';

    /** @var string */
    private $testsNamespace = '';

    /** @var string */
    private $testsDir = '';

    public function setAutoloadingStrategy(string $autoloadingStrategy) : self
    {
        $this->autoloadingStrategy = $autoloadingStrategy;

        return $this;
    }

    public function setSourceNamespace(string $sourceNamespace) : self
    {
        $this->sourceNamespace = $sourceNamespace;

        return $this;
    }

    public function setSourceDir(string $sourceDir) : self
    {
        $this->sourceDir = $sourceDir;

        return $this;
    }

    public function setTestsNamespace(string $testsNamespace) : self
    {
        $this->testsNamespace = $testsNamespace;

        return $this;
    }

    public function setTestsDir(string $testsDir) : self
    {
        $this->testsDir = $testsDir;

        return $this;
    }

    public function build() : Configuration
    {
        return new Configuration(
            $this->autoloadingStrategy,
            $this->sourceNamespace,
            $this->sourceDir,
            $this->testsNamespace,
            $this->testsDir
        );
    }
}
