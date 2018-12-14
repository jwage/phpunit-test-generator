<?php

declare (strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests;

use JWage\PHPUnitTestGenerator\InflectorFactory;
use PHPUnit\Framework\TestCase;

class InflectorFactoryTest extends TestCase
{
    public function testCreateEnglishInflector() : void
    {
        $inflector = InflectorFactory::createEnglishInflector();

        self::assertSame('apple', $inflector->singularize('apples'));
    }
}
