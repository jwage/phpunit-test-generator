<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

use Doctrine\Inflector\Inflector;
use JWage\PHPUnitTestGenerator\Configuration\Configuration;
use ReflectionClass;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function str_replace;

class TestClassGenerator
{
    /** @var Configuration */
    private $configuration;

    /** @var Inflector */
    private $inflector;

    /** @var ReflectionClass */
    private $reflectionClass;

    /** @var string */
    private $classShortName;

    /** @var string */
    private $classCamelCaseName;

    /** @var string */
    private $testNamespace;

    /** @var string */
    private $testClassShortName;

    /** @var string */
    private $testClassName;

    public function __construct(
        Configuration $configuration,
        ?Inflector $inflector = null
    ) {
        $this->configuration = $configuration;
        $this->inflector     = $inflector ?? InflectorFactory::createEnglishInflector();
    }

    public function generate(string $className) : GeneratedTestClass
    {
        $this->reflectionClass = new ReflectionClass($className);

        $this->classShortName     = $this->reflectionClass->getShortName();
        $this->classCamelCaseName = $this->inflector->camelize($this->classShortName);
        $this->testNamespace      = str_replace(
            $this->configuration->getSourceNamespace(),
            $this->configuration->getTestsNamespace(),
            $this->reflectionClass->getNamespaceName()
        );
        $this->testClassShortName = $this->classShortName . 'Test';
        $this->testClassName      = $this->testNamespace . '\\' . $this->testClassShortName;

        $testClassMetadata = (new TestClassMetadataParser(
            $this->inflector
        ))->getTestClassMetadata($className);

        $code = $this->renderTestClassCode($testClassMetadata);

        return new GeneratedTestClass(
            $className,
            $this->testClassName,
            $code
        );
    }

    private function renderTestClassCode(TestClassMetadata $testClassMetadata) : string
    {
        return $this->createTwigEnvironment()->render('test-class.html.twig', [
            'testClassMetadata' => $testClassMetadata,
            'classCamelCaseName' => $this->classCamelCaseName,
            'namespace' => $this->testNamespace,
            'shortName' => $this->testClassShortName,
        ]);
    }

    private function createTwigEnvironment() : Environment
    {
        $loader = new FilesystemLoader([__DIR__ . '/Templates']);

        return new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => false,
        ]);
    }
}
