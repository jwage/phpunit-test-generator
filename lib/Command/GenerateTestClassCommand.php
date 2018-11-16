<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Command;

use InvalidArgumentException;
use JWage\PHPUnitTestGenerator\Configuration\AutoloadingStrategy;
use JWage\PHPUnitTestGenerator\Configuration\ComposerConfigurationReader;
use JWage\PHPUnitTestGenerator\Configuration\Configuration;
use JWage\PHPUnitTestGenerator\TestClassGenerator;
use JWage\PHPUnitTestGenerator\Writer\Psr4TestClassWriter;
use JWage\PHPUnitTestGenerator\Writer\TestClassWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

class GenerateTestClassCommand extends Command
{
    protected function configure() : void
    {
        $this
            ->setName('generate-test-class')
            ->setDescription('Generate a PHPUnit test class from a class.')
            ->addArgument('class', InputArgument::REQUIRED, 'The class name to generate the test for.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $className = $input->getArgument('class');

        if ($className === '') {
            throw new InvalidArgumentException('Specify class name to generate unit test for');
        }

        $configuration = $this->createConfiguration();

        $generateTestClass = $this->createTestClassGenerator($configuration);

        $this->createTestClassWriter($configuration)
            ->write($generateTestClass->generate($className));
    }

    private function createConfiguration() : Configuration
    {
        return (new ComposerConfigurationReader())->createConfiguration();
    }

    private function createTestClassGenerator(Configuration $configuration) : TestClassGenerator
    {
        return new TestClassGenerator($configuration);
    }

    private function createTestClassWriter(Configuration $configuration) : TestClassWriter
    {
        $autoloadingStrategy = $configuration->getAutoloadingStrategy();

        if ($autoloadingStrategy === AutoloadingStrategy::PSR4) {
            return new Psr4TestClassWriter($configuration);
        }

        throw new InvalidArgumentException(
            sprintf('Autoloading strategy not supported %s not supported', $autoloadingStrategy)
        );
    }
}
