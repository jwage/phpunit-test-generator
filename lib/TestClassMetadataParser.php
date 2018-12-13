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
use function class_exists;
use function count;
use function sort;
use function sprintf;
use function substr;
use function ucfirst;

class TestClassMetadataParser
{
    public const DEPENDENCY = 'dependency';
    public const NORMAL     = 'normal';
    public const SUT        = 'sut';

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
            $this->generateSetUpLines(),
            $this->generateTestMethods()
        );
    }

    /**
     * @return mixed[]
     */
    private function generateUseStatements() : array
    {
        $useStatements   = [];
        $useStatements[] = $this->reflectionClass->name;
        $useStatements[] = TestCase::class;

        $parameters = $this->getConstructorParameters();

        if (count($parameters) !== 0) {
            foreach ($parameters as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass === null) {
                    continue;
                }

                $useStatements[] = $parameterClass->getName();
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

                $useStatements[] = $parameterClass->getName();
            }

            $returnType = $method->getReturnType();

            if ($returnType !== null) {
                $returnTypeName = $returnType->getName();

                if (! class_exists($returnTypeName)) {
                    continue;
                }
            }

            $useStatements[] = $returnType;
        }

        $useStatements[] = MockObject::class;

        $useStatements = array_unique($useStatements);

        sort($useStatements);

        return $useStatements;
    }

    /**
     * @return mixed[]
     */
    private function generateClassProperties() : array
    {
        $classProperties = [];

        $parameters = $this->getConstructorParameters();

        foreach ($parameters as $key => $parameter) {
            $parameterClass = $parameter->getClass();

            if ($parameterClass !== null) {
                $classProperties[] = [
                    'type' => self::DEPENDENCY,
                    'propertyType' => $parameterClass->getShortName(),
                    'propertyName' => $parameter->name,
                ];
            } else {
                $classProperties[] = [
                    'type' => self::NORMAL,
                    'propertyType' => (string) $parameter->getType(),
                    'propertyName' => $parameter->name,
                ];
            }
        }

        $classProperties[] = [
            'type' => self::NORMAL,
            'propertyType' => $this->classShortName,
            'propertyName' => $this->classCamelCaseName,
        ];

        return $classProperties;
    }

    /**
     * @return mixed[]
     */
    private function generateSetUpLines() : array
    {
        $classShortName     = $this->reflectionClass->getShortName();
        $classCamelCaseName = $this->inflector->camelize($classShortName);

        $setUpLines = [];

        $parameters = $this->getConstructorParameters();

        foreach ($parameters as $parameter) {
            $parameterClass = $parameter->getClass();

            if ($parameterClass !== null) {
                $setUpLines[] = [
                    'type' => self::DEPENDENCY,
                    'propertyName' => $parameter->name,
                    'propertyType' => $parameterClass->getShortName(),
                ];
            } else {
                $typeRandomValue = $this->generateTypeRandomValue((string) $parameter->getType());

                $setUpLines[] = [
                    'type' => self::NORMAL,
                    'propertyName' => $parameter->name,
                    'propertyValue' => $typeRandomValue,
                ];
            }
        }

        $setUpLines[] = [
            'type' => self::SUT,
            'propertyName' => $classCamelCaseName,
            'propertyType' => $classShortName,
            'arguments' => array_map(static function (ReflectionParameter $parameter) {
                return $parameter->name;
            }, $parameters),
        ];

        return $setUpLines;
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
                'methodName' => sprintf('test%s', ucfirst($method->name)),
                'lines' => $this->generateTestMethodLines($method),
            ];
        }

        return $testMethods;
    }

    /**
     * @return mixed[]
     */
    private function generateTestMethodLines(ReflectionMethod $method) : array
    {
        $parameters = $method->getParameters();

        $testMethodLines = [];

        foreach ($parameters as $parameter) {
            $parameterClass = $parameter->getClass();

            if ($parameterClass !== null) {
                $testMethodLines[] = [
                    'type' => self::DEPENDENCY,
                    'variableName' => $parameter->name,
                    'variableType' => $parameterClass->getShortName(),
                ];
            } else {
                $testMethodLines[] = [
                    'type' => self::NORMAL,
                    'variableName' => $parameter->name,
                ];
            }
        }

        $returnType     = $method->getReturnType();
        $returnTypeName = '';

        if ($returnType !== null) {
            $returnTypeName = $returnType->getName();
        }

        $testMethodLines[] = [
            'type' => self::SUT,
            'variableName' => $this->classCamelCaseName,
            'methodName' => $method->name,
            'methodReturnType' => $returnTypeName,
            'arguments' => array_map(static function (ReflectionParameter $parameter) {
                return $parameter->name;
            }, $parameters),
        ];

        return $testMethodLines;
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
            case 'array':
                return [];

            case 'bool':
                return true;

            case 'float':
                return 1.0;

            case 'int':
                return 1;

            case 'string':
                return '';
        }

        return '';
    }
}
