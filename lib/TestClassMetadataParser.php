<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

use Doctrine\Inflector\Inflector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use function array_map;
use function array_unique;
use function count;
use function sort;
use function substr;
use function var_export;

class TestClassMetadataParser
{
    /** @var Inflector */
    private $inflector;

    /** @var ReflectionClass */
    private $reflectionClass;

    /** @var string */
    private $classShortName;

    /** @var string */
    private $classCamelCaseName;

    public function __construct(?Inflector $inflector = null)
    {
        $this->inflector = $inflector ?? InflectorFactory::createEnglishInflector();
    }

    public function getTestClassMetadata(string $className) : TestClassMetadata
    {
        $this->reflectionClass = new ReflectionClass($className);

        $this->classShortName     = $this->reflectionClass->getShortName();
        $this->classCamelCaseName = $this->inflector->camelize($this->classShortName);

        return new TestClassMetadata(
            $this->generateUseStatements(),
            $this->generateClassProperties(),
            $this->generateSetUpDependencies(),
            $this->generateTestMethods()
        );
    }

    /**
     * @return mixed[]
     */
    private function generateUseStatements() : array
    {
        $dependencies   = [];
        $dependencies[] = $this->reflectionClass->name;
        $dependencies[] = TestCase::class;

        $parameters = $this->getConstructorParameters();

        if (count($parameters) !== 0) {
            foreach ($parameters as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass === null) {
                    continue;
                }

                $dependencies[] = $parameterClass->getName();
            }
        }

        foreach ($this->reflectionClass->getMethods() as $method) {
            if (! $this->isMethodTestable($method)) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass === null) {
                    continue;
                }

                $dependencies[] = $parameterClass->getName();
            }
        }

        $dependencies[] = MockObject::class;

        sort($dependencies);

        $dependencies = array_unique($dependencies);

        return $dependencies;
    }

    /**
     * @return mixed[]
     */
    private function generateClassProperties() : array
    {
        $testProperties = [];

        $parameters = $this->getConstructorParameters();

        foreach ($parameters as $key => $parameter) {
            $parameterClass = $parameter->getClass();

            if ($parameterClass !== null) {
                $testProperties[] = [
                    'type' => 'dependency',
                    'propertyType' => $parameterClass->getShortName(),
                    'propertyName' => $parameter->name,
                ];
            } else {
                $testProperties[] = [
                    'type' => 'normal',
                    'propertyType' => (string) $parameter->getType(),
                    'propertyName' => $parameter->name,
                ];
            }
        }

        $testProperties[] = [
            'type' => 'normal',
            'propertyType' => $this->classShortName,
            'propertyName' => $this->classCamelCaseName,
        ];

        return $testProperties;
    }

    /**
     * @return mixed[]
     */
    private function generateSetUpDependencies() : array
    {
        $classShortName     = $this->reflectionClass->getShortName();
        $classCamelCaseName = $this->inflector->camelize($classShortName);

        $setUpDependencies = [];

        $parameters = $this->getConstructorParameters();

        if (count($parameters) !== 0) {
            foreach ($parameters as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass !== null) {
                    $setUpDependencies[] = [
                        'type' => 'dependency',
                        'propertyName' => $parameter->name,
                        'propertyType' => $parameterClass->getShortName(),
                    ];
                } else {
                    $typeRandomValue = $this->generateTypeRandomValue((string) $parameter->getType());

                    $setUpDependencies[] = [
                        'type' => 'normal',
                        'propertyName' => $parameter->name,
                        'propertyValue' => var_export($typeRandomValue, true),
                    ];
                }
            }

            $setUpDependencies[] = [
                'type' => 'sut',
                'propertyName' => $classCamelCaseName,
                'propertyType' => $classShortName,
                'parameters' => array_map(static function (ReflectionParameter $parameter) {
                    return $parameter->name;
                }, $parameters),
            ];
        } else {
            $setUpDependencies[] = [
                'type' => 'sut',
                'propertyName' => $classCamelCaseName,
                'propertyType' => $classShortName,
                'parameters' => [],
            ];
        }

        return $setUpDependencies;
    }

    /**
     * @return ReflectionParameter[]
     */
    private function getConstructorParameters() : array
    {
        $constructor = $this->reflectionClass->getConstructor();

        if ($constructor !== null) {
            return $constructor->getParameters();
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    private function generateTestMethods() : array
    {
        $testMethods = [];

        foreach ($this->reflectionClass->getMethods() as $method) {
            if (! $this->isMethodTestable($method)) {
                continue;
            }

            $testMethods[] = [
                'methodName' => $method->name,
                'body' => $this->generateTestMethodBody($method),
            ];
        }

        return $testMethods;
    }

    /**
     * @return mixed[]
     */
    private function generateTestMethodBody(ReflectionMethod $method) : array
    {
        $parameters = $method->getParameters();

        $testMethodBody = [];

        if (count($parameters) !== 0) {
            foreach ($parameters as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass !== null) {
                    $testMethodBody[] = [
                        'type' => 'dependency',
                        'parameterName' => $parameter->name,
                        'parameterType' => $parameterClass->getShortName(),
                    ];
                } else {
                    $testMethodBody[] = [
                        'type' => 'normal',
                        'parameterName' => $parameter->name,
                    ];
                }
            }

            $testMethodBody[] = [
                'type' => 'sut',
                'parameterName' => $this->classCamelCaseName,
                'methodName' => $method->name,
                'parameters' => array_map(static function (ReflectionParameter $parameter) {
                    return $parameter->name;
                }, $parameters),
            ];
        } else {
            $testMethodBody[] = [
                'type' => 'sut',
                'parameterName' => $this->classCamelCaseName,
                'methodName' => $method->name,
                'parameters' => [],
            ];
        }

        return $testMethodBody;
    }

    private function isMethodTestable(ReflectionMethod $method) : bool
    {
        if ($this->reflectionClass->name !== $method->class) {
            return false;
        }

        return substr($method->name, 0, 2) !== '__' && $method->isPublic();
    }

    /**
     * @return mixed
     */
    private function generateTypeRandomValue(string $type)
    {
        switch ($type) {
            case 'string':
                return '';

            case 'float':
                return 1.0;

            case 'int':
                return 1;
        }

        return '';
    }
}
