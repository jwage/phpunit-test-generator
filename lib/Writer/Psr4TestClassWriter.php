<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Writer;

use JWage\PHPUnitTestGenerator\Configuration\Configuration;
use JWage\PHPUnitTestGenerator\GeneratedTestClass;
use RuntimeException;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sprintf;
use function str_replace;

class Psr4TestClassWriter implements TestClassWriter
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function write(GeneratedTestClass $generatedTestClass) : void
    {
        $writePath = $this->generatePsr4TestWritePath($generatedTestClass);

        $writeDirectory = dirname($writePath);

        if (! is_dir($writeDirectory)) {
            mkdir($writeDirectory, 0777, true);
        }

        if (file_exists($writePath)) {
            throw new RuntimeException(sprintf('File already exists at path %s', $writePath));
        }

        file_put_contents(
            $writePath,
            $generatedTestClass->getCode()
        );
    }

    private function generatePsr4TestWritePath(GeneratedTestClass $generatedTestClass) : string
    {
        $writePath = $this->configuration->getTestsDir();

        $writePath .= '/' . str_replace(
            $this->configuration->getTestsNamespace() . '\\',
            '',
            $generatedTestClass->getTestClassName()
        ) . '.php';

        $writePath = str_replace('\\', DIRECTORY_SEPARATOR, $writePath);

        return $writePath;
    }
}
