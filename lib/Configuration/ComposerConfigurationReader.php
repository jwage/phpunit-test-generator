<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Configuration;

use RuntimeException;
use function assert;
use function file_exists;
use function file_get_contents;
use function getcwd;
use function is_string;
use function json_decode;
use function key;
use function rtrim;
use function sprintf;

class ComposerConfigurationReader
{
    public function createConfiguration(?string $path = null) : Configuration
    {
        $path             = $path ?? getcwd();
        $composerJsonPath = $path . '/composer.json';

        if (! file_exists($composerJsonPath)) {
            throw new RuntimeException(
                sprintf('Could not find composer.json in the current working directory')
            );
        }

        $json = file_get_contents($composerJsonPath);

        if ($json === false) {
            throw new RuntimeException(
                sprintf('Could not read composer.json')
            );
        }

        $composerJsonData = json_decode($json, true);

        if ($this->isPsr4($composerJsonData)) {
            return $this->getPsr4Configuration($composerJsonData);
        }

        throw new RuntimeException('Only psr4 is currently supported. Pull Requests accepted to support other autoloading standards.');
    }

    /**
     * @param mixed[] $composerJsonData
     */
    private function getPsr4Configuration(array $composerJsonData) : Configuration
    {
        [$sourceNamespace, $sourceDir] = $this->getPsr4Source($composerJsonData);
        [$testsNamespace, $testsDir]   = $this->getPsr4Tests($composerJsonData);

        return (new ConfigurationBuilder())
            ->setAutoloadingStrategy(AutoloadingStrategy::PSR4)
            ->setSourceNamespace(rtrim($sourceNamespace, '\\'))
            ->setSourceDir(rtrim($sourceDir, '/'))
            ->setTestsNamespace(rtrim($testsNamespace, '\\'))
            ->setTestsDir(rtrim($testsDir, '/'))
            ->build();
    }

    /**
     * @param mixed[] $composerJsonData
     */
    private function isPsr4(array $composerJsonData) : bool
    {
        return isset($composerJsonData['autoload']['psr-4'])
            && isset($composerJsonData['autoload-dev']['psr-4']);
    }

    /**
     * @param mixed[] $composerJsonData
     *
     * @return string[]
     */
    private function getPsr4Source(array $composerJsonData) : array
    {
        return $this->getNamespaceSourcePair($composerJsonData['autoload']['psr-4']);
    }

    /**
     * @param mixed[] $composerJsonData
     *
     * @return string[]
     */
    private function getPsr4Tests(array $composerJsonData) : array
    {
        return $this->getNamespaceSourcePair($composerJsonData['autoload-dev']['psr-4']);
    }

    /**
     * @param string[] $psr
     *
     * @return string[]
     */
    private function getNamespaceSourcePair(array $psr) : array
    {
        $sourceNamespace = key($psr);
        assert(is_string($sourceNamespace));

        $sourceDir = getcwd() . '/' . $psr[$sourceNamespace];

        return [$sourceNamespace, $sourceDir];
    }
}
