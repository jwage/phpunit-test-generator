<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Writer;

use JWage\PHPUnitTestGenerator\GeneratedTestClass;

interface TestClassWriter
{
    public function write(GeneratedTestClass $generatedTestClass) : void;
}
