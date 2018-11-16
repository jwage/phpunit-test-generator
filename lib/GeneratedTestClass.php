<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator;

class GeneratedTestClass
{
    /** @var string */
    private $className;

    /** @var string */
    private $testClassName;

    /** @var string */
    private $code;

    public function __construct(string $className, string $testClassName, string $code)
    {
        $this->className     = $className;
        $this->testClassName = $testClassName;
        $this->code          = $code;
    }

    public function getClassName() : string
    {
        return $this->className;
    }

    public function getTestClassName() : string
    {
        return $this->testClassName;
    }

    public function getCode() : string
    {
        return $this->code;
    }
}
