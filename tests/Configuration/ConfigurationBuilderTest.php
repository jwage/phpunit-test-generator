<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests\Configuration;

use JWage\PHPUnitTestGenerator\Configuration\AutoloadingStrategy;
use JWage\PHPUnitTestGenerator\Configuration\ConfigurationBuilder;
use PHPUnit\Framework\TestCase;

class ConfigurationBuilderTest extends TestCase
{
    /** @var ConfigurationBuilder */
    private $configurationBuilder;

    public function testSetAutoloadingStrategy() : void
    {
        $autoloadingStrategy = AutoloadingStrategy::PSR4;

        $this->configurationBuilder->setAutoloadingStrategy($autoloadingStrategy);

        $configuration = $this->configurationBuilder->build();

        self::assertSame($autoloadingStrategy, $configuration->getAutoloadingStrategy());
    }

    public function testSetSourceNamespace() : void
    {
        $sourceNamespace = 'App';

        $this->configurationBuilder->setSourceNamespace($sourceNamespace);

        $configuration = $this->configurationBuilder->build();

        self::assertSame($sourceNamespace, $configuration->getSourceNamespace());
    }

    public function testSetSourceDir() : void
    {
        $sourceDir = '/source/dir';

        $this->configurationBuilder->setSourceDir($sourceDir);

        $configuration = $this->configurationBuilder->build();

        self::assertSame($sourceDir, $configuration->getSourceDir());
    }

    public function testSetTestsNamespace() : void
    {
        $testsNamespace = 'App\Tests';

        $this->configurationBuilder->setTestsNamespace($testsNamespace);

        $configuration = $this->configurationBuilder->build();

        self::assertSame($testsNamespace, $configuration->getTestsNamespace());
    }

    public function testSetTestsDir() : void
    {
        $testsDir = '/tests/dir';

        $this->configurationBuilder->setTestsDir($testsDir);

        $configuration = $this->configurationBuilder->build();

        self::assertSame($testsDir, $configuration->getTestsDir());
    }

    protected function setUp() : void
    {
        $this->configurationBuilder = new ConfigurationBuilder();
    }
}
