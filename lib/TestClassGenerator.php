<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

use Doctrine\Inflector\Inflector;
use JWage\PHPUnitTestGenerator\Configuration\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function array_map;
use function array_unique;
use function count;
use function implode;
use function sort;
use function sprintf;
use function str_replace;
use function substr;
use function trim;
use function ucfirst;
use function var_export;

class TestClassGenerator
{
    private const CLASS_TEMPLATE = <<<EOF
<?php

declare(strict_types=1);

namespace {{ namespace }};

{{ useStatements }}

class {{ shortName }} extends TestCase
{
{{ properties }}

{{ methods }}

{{ setUpCode }}
}

EOF;

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

    /** @var string */
    private $useStatementsCode;

    /** @var string */
    private $testPropertiesCode;

    /** @var string */
    private $setUpCode;

    /** @var string */
    private $testMethodsCode;

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

        $this->useStatementsCode  = $this->generateUseStatements();
        $this->testPropertiesCode = $this->generateClassProperties();
        $this->setUpCode          = $this->generateSetUp();
        $this->testMethodsCode    = $this->generateTestMethods();

        $loader = new ArrayLoader(['template' => self::CLASS_TEMPLATE]);

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => false,
        ]);

        $code = $twig->render('template', [
            'classShortName' => $this->classShortName,
            'classCamelCaseName' => $this->classCamelCaseName,
            'namespace' => $this->testNamespace,
            'shortName' => $this->testClassShortName,
            'methods' => $this->testMethodsCode,
            'properties' => $this->testPropertiesCode,
            'useStatements' => $this->useStatementsCode,
            'setUpCode' => $this->setUpCode,
        ]);

        return new GeneratedTestClass($className, $this->testClassName, $code);
    }

    private function generateClassProperties() : string
    {
        $testPropertiesCode = [];

        $parameters = $this->getConstructorParameters();

        foreach ($parameters as $key => $parameter) {
            $isLast = $key === count($parameters) - 1;

            $parameterClass = $parameter->getClass();

            if ($parameterClass !== null) {
                $testPropertiesCode[] = '    /** @var ' . $parameterClass->getShortName() . '|MockObject */';
                $testPropertiesCode[] = '    private $' . $parameter->name . ';';

                if (! $isLast) {
                    $testPropertiesCode[] = '';
                }
            } else {
                $testPropertiesCode[] = '    /** @var ' . $parameter->getType() . ' */';
                $testPropertiesCode[] = '    private $' . $parameter->name . ';';

                if (! $isLast) {
                    $testPropertiesCode[] = '';
                }
            }
        }

        if (count($parameters) !== 0) {
            $testPropertiesCode[] = '';
        }

        $testPropertiesCode[] = '    /** @var ' . $this->classShortName . ' */';
        $testPropertiesCode[] = '    private $' . $this->classCamelCaseName . ';';

        return implode("\n", $testPropertiesCode);
    }

    private function generateSetUp() : string
    {
        $classShortName     = $this->reflectionClass->getShortName();
        $classCamelCaseName = $this->inflector->camelize($classShortName);

        $setUpCode   = [];
        $setUpCode[] = '    protected function setUp() : void';
        $setUpCode[] = '    {';

        $parameters = $this->getConstructorParameters();

        if (count($parameters) !== 0) {
            foreach ($parameters as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass !== null) {
                    $setUpCode[] = sprintf(
                        '        $this->%s = $this->createMock(%s::class);',
                        $parameter->name,
                        $parameterClass->getShortName()
                    );
                } else {
                    $typeRandomValue = $this->generateTypeRandomValue($parameter->getType()->getName());

                    $setUpCode[] = sprintf(
                        '        $this->%s = %s;',
                        $parameter->name,
                        var_export($typeRandomValue, true)
                    );
                }
            }

            $setUpCode[] = '';
            $setUpCode[] = sprintf('        $this->%s = new %s(', $classCamelCaseName, $classShortName);

            // arguments for class being tested
            $setUpCodeArguments = [];
            foreach ($parameters as $parameter) {
                $setUpCodeArguments[] = sprintf('            $this->%s', $parameter->name);
            }
            $setUpCode[] = implode(",\n", $setUpCodeArguments);

            $setUpCode[] = '        );';
        } else {
            $setUpCode[] = sprintf('        $this->%s = new %s();', $classCamelCaseName, $classShortName);
        }

        $setUpCode[] = '    }';

        return implode("\n", $setUpCode);
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

    private function generateTestMethods() : string
    {
        $testMethodsCode = [];

        foreach ($this->reflectionClass->getMethods() as $method) {
            if (! $this->isMethodTestable($method)) {
                continue;
            }

            $testMethodsCode[] = sprintf('    public function test%s() : void', ucfirst($method->name));
            $testMethodsCode[] = '    {';
            $testMethodsCode[] = $this->generateTestMethodBody($method);
            $testMethodsCode[] = '    }';
            $testMethodsCode[] = '';
        }

        return '    ' . trim(implode("\n", $testMethodsCode));
    }

    private function generateTestMethodBody(ReflectionMethod $method) : string
    {
        $parameters = $method->getParameters();

        $testMethodBodyCode = [];

        if (count($parameters) !== 0) {
            foreach ($parameters as $parameter) {
                $parameterClass = $parameter->getClass();

                if ($parameterClass !== null) {
                    $testMethodBodyCode[] = sprintf(
                        '        $%s = $this->createMock(%s::class);',
                        $parameter->name,
                        $parameterClass->getShortName()
                    );
                } else {
                    $testMethodBodyCode[] = sprintf("        \$%s = '';", $parameter->name);
                }
            }

            $testMethodBodyCode[] = '';
            $testMethodBodyCode[] = sprintf('        $this->%s->%s(', $this->classCamelCaseName, $method->name);

            $testMethodParameters = [];
            foreach ($parameters as $parameter) {
                $testMethodParameters[] = sprintf('$%s', $parameter->name);
            }

            $testMethodBodyCode[] = '            ' . implode(",\n            ", $testMethodParameters);
            $testMethodBodyCode[] = '        );';
        } else {
            $testMethodBodyCode[] = sprintf('        $this->%s->%s();', $this->classCamelCaseName, $method->name);
        }

        return implode("\n", $testMethodBodyCode);
    }

    private function generateUseStatements() : string
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

        $useStatementsCode = array_map(static function ($dependency) {
            return sprintf('use %s;', $dependency);
        }, $dependencies);

        return implode("\n", $useStatementsCode);
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
