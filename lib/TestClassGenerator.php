<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

use Doctrine\Inflector\Inflector;
use JWage\PHPUnitTestGenerator\Configuration\Configuration;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use ReflectionClass;
use function array_map;
use function class_exists;
use function implode;
use function sprintf;
use function str_replace;

class TestClassGenerator
{
    /** @var Configuration */
    private $configuration;

    /** @var Inflector */
    private $inflector;

    /** @var BuilderFactory */
    private $builderFactory;

    /** @var ReflectionClass */
    private $reflectionClass;

    /** @var string */
    private $classShortName;

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
        $this->configuration  = $configuration;
        $this->inflector      = $inflector ?? InflectorFactory::createEnglishInflector();
        $this->builderFactory = new BuilderFactory();
    }

    public function generate(string $className) : GeneratedTestClass
    {
        $this->init($className);

        $testClassMetadata = (new TestClassMetadataParser(
            $this->inflector
        ))->getTestClassMetadata($className);

        $code = $this->generateTestClass($testClassMetadata);

        $code = $this->replaceWithNewLines($code);

        return new GeneratedTestClass(
            $className,
            $this->testClassName,
            $code
        );
    }

    private function replaceWithNewLines(string $code) : string
    {
        $code = str_replace('    private $__newLineReplace__;', '', $code);

        $code = str_replace(<<<CODE
    function __newLineReplace__()
    {
    }
CODE
        , '', $code);

        return $code . "\n";
    }

    private function generateTestClass(TestClassMetadata $testClassMetadata) : string
    {
        $nodes = $this->generateTestClassNodes($testClassMetadata);

        return (new PrettyPrinter\Standard())
            ->prettyPrintFile($nodes);
    }

    /**
     * @return Node[]
     */
    private function generateTestClassNodes(TestClassMetadata $testClassMetadata) : array
    {
        $nodes = [];

        $nodes[] = new Node\Stmt\Declare_([new Node\Stmt\DeclareDeclare('strict_types', $this->builderFactory->val(1))]);

        $nodes[] = new Node\Stmt\Nop();

        $namespaceBuilder = $this->builderFactory->namespace($this->testNamespace);

        foreach ($testClassMetadata->getUseStatements() as $useStatement) {
            $namespaceBuilder->addStmt($this->builderFactory->use($useStatement));
        }

        $namespaceBuilder->addStmt(new Node\Stmt\Nop());

        $classBuilder = $this->builderFactory->class($this->testClassShortName)
            ->extend('TestCase');

        $this->generateTestClassProperties($testClassMetadata, $classBuilder);
        $this->generateTestClassTestMethods($testClassMetadata, $classBuilder);
        $this->generateTestClassSetUpMethod($testClassMetadata, $classBuilder);

        $namespaceBuilder->addStmt($classBuilder);

        $nodes[] = $namespaceBuilder->getNode();

        return $nodes;
    }

    private function generateTestClassProperties(
        TestClassMetadata $testClassMetadata,
        Class_ $classBuilder
    ) : void {
        foreach ($testClassMetadata->getProperties() as $property) {
            $classBuilder->addStmt(
                $this->builderFactory->property($property['propertyName'])
                    ->makePrivate()
                    ->setDocComment($this->generatePropertyDocBlock($property))
            );

            $classBuilder->addStmt(
                $this->builderFactory->property('__newLineReplace__')
                    ->makePrivate()
            );
        }
    }

    /**
     * @param string[] $property
     */
    private function generatePropertyDocBlock(array $property) : string
    {
        $docBlockTypes = [$property['propertyType']];

        if ($property['type'] === TestClassMetadataParser::DEPENDENCY) {
            $docBlockTypes[] = 'MockObject';
        }

        return sprintf('/** @var %s */', implode('|', $docBlockTypes));
    }

    private function generateTestClassTestMethods(
        TestClassMetadata $testClassMetadata,
        Class_ $classBuilder
    ) : void {
        foreach ($testClassMetadata->getTestMethods() as $testMethod) {
            $methodBuilder = $this->builderFactory->method($testMethod['methodName'])
                ->makePublic()
                ->setReturnType('void');

            foreach ($testMethod['lines'] as $line) {
                $this->generateTestClassMethodLine($methodBuilder, $line);
            }

            $classBuilder->addStmt($methodBuilder);

            $classBuilder->addStmt($this->builderFactory->method('__newLineReplace__'));
        }
    }

    /**
     * @param mixed[] $line
     */
    private function generateTestClassMethodLine(Method $methodBuilder, array $line) : void
    {
        switch ($line['type']) {
            case TestClassMetadataParser::DEPENDENCY:
                $methodBuilder->addStmt(new Node\Expr\Assign(
                    $this->builderFactory->var($line['variableName']),
                    $this->createMockMethodCall($line['variableType'])
                ));

                break;

            case TestClassMetadataParser::NORMAL:
                $methodBuilder->addStmt(new Node\Expr\Assign(
                    $this->builderFactory->var($line['variableName']),
                    $this->builderFactory->val('')
                ));

                break;

            case TestClassMetadataParser::SUT:
                $arguments = $this->builderFactory->args(array_map(function (string $parameter) {
                    return $this->builderFactory->var($parameter);
                }, $line['arguments']));

                $assertArguments = [];
                $assertMethod    = 'assertNull';

                switch ($line['methodReturnType']) {
                    case 'null':
                        $assertMethod = 'assertNull';
                        break;

                    case 'string':
                        $assertMethod      = 'assertSame';
                        $assertArguments[] = '';
                        break;

                    case 'int':
                        $assertMethod      = 'assertSame';
                        $assertArguments[] = 1;
                        break;

                    case 'float':
                        $assertMethod      = 'assertSame';
                        $assertArguments[] = 1.0;
                        break;

                    case 'bool':
                        $assertMethod = 'assertTrue';
                        break;

                    case 'array':
                        $assertMethod      = 'assertSame';
                        $assertArguments[] = [];
                        break;

                    default:
                        if (class_exists($line['methodReturnType'])) {
                            $reflectionClass = new ReflectionClass($line['methodReturnType']);

                            $assertMethod      = 'assertInstanceOf';
                            $assertArguments[] = $this->builderFactory->classConstFetch(
                                $reflectionClass->getShortName(),
                                'class'
                            );
                        }
                }

                $assertArguments[] = $this->builderFactory->methodCall(
                    $this->builderFactory->var('this->' . $line['variableName']),
                    $line['methodName'],
                    $arguments
                );

                $methodBuilder->addStmt(
                    $this->builderFactory->staticCall(
                        'self',
                        $assertMethod,
                        $assertArguments
                    )
                );

                break;
        }
    }

    private function generateTestClassSetUpMethod(
        TestClassMetadata $testClassMetadata,
        Class_ $classBuilder
    ) : void {
        $methodBuilder = $this->builderFactory->method('setUp')
            ->makeProtected()
            ->setReturnType('void');

        foreach ($testClassMetadata->getSetUpDependencies() as $setUpDependency) {
            switch ($setUpDependency['type']) {
                case TestClassMetadataParser::DEPENDENCY:
                    $methodBuilder->addStmt(new Node\Expr\Assign(
                        $this->builderFactory->var('this->' . $setUpDependency['propertyName']),
                        $this->createMockMethodCall($setUpDependency['propertyType'])
                    ));

                    break;

                case TestClassMetadataParser::NORMAL:
                    $methodBuilder->addStmt(new Node\Expr\Assign(
                        $this->builderFactory->var('this->' . $setUpDependency['propertyName']),
                        $this->builderFactory->val($setUpDependency['propertyValue'])
                    ));

                    break;

                case TestClassMetadataParser::SUT:
                    $arguments = array_map(function (string $argument) {
                        return $this->builderFactory->var('this->' . $argument);
                    }, $setUpDependency['arguments']);

                    $methodBuilder->addStmt(new Node\Expr\Assign(
                        $this->builderFactory->var('this->' . $setUpDependency['propertyName']),
                        $this->builderFactory->new(
                            new Node\Name($setUpDependency['propertyType']),
                            $arguments
                        )
                    ));

                    break;
            }
        }

        $classBuilder->addStmt($methodBuilder);
    }

    private function createMockMethodCall(string $className) : Node\Expr\MethodCall
    {
        return $this->builderFactory->methodCall(
            $this->builderFactory->var('this'),
            'createMock',
            [$this->builderFactory->classConstFetch($className, 'class')]
        );
    }

    private function init(string $className) : void
    {
        $this->reflectionClass = new ReflectionClass($className);

        $this->classShortName     = $this->reflectionClass->getShortName();
        $this->testNamespace      = str_replace(
            $this->configuration->getSourceNamespace(),
            $this->configuration->getTestsNamespace(),
            $this->reflectionClass->getNamespaceName()
        );
        $this->testClassShortName = $this->classShortName . 'Test';
        $this->testClassName      = $this->testNamespace . '\\' . $this->testClassShortName;
    }
}
