<?php

declare(strict_types=1);

namespace JWage\PHPUnitTestGenerator\Tests\Writer;

use JWage\PHPUnitTestGenerator\Configuration\AutoloadingStrategy;
use JWage\PHPUnitTestGenerator\Configuration\Configuration;
use JWage\PHPUnitTestGenerator\Configuration\ConfigurationBuilder;
use JWage\PHPUnitTestGenerator\GeneratedTestClass;
use JWage\PHPUnitTestGenerator\Writer\Psr4TestClassWriter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class Psr4TestClassWriterTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var Filesystem|MockObject */
    private $filesystem;

    /** @var Psr4TestClassWriter */
    private $psr4TestClassWriter;

    public function testWrite() : void
    {
        $generatedTestClass = new GeneratedTestClass(
            'App\User',
            'App\Tests\UserTest',
            '<?php echo "Hello World";'
        );

        $this->filesystem->expects(self::at(0))
            ->method('exists')
            ->with('/data/tests')
            ->willReturn(false);

        $this->filesystem->expects(self::once())
            ->method('mkdir')
            ->with('/data/tests');

        $this->filesystem->expects(self::at(2))
            ->method('exists')
            ->with('/data/tests/UserTest.php')
            ->willReturn(false);

        $this->filesystem->expects(self::at(3))
            ->method('dumpFile')
            ->with('/data/tests/UserTest.php', '<?php echo "Hello World";');

        $writePath = $this->psr4TestClassWriter->write($generatedTestClass);

        self::assertSame('/data/tests/UserTest.php', $writePath);
    }


    public function testWriteTestClassAlreadyExists() : void
    {
        $generatedTestClass = new GeneratedTestClass(
            'App\User',
            'App\Tests\UserTest',
            '<?php echo "Hello World";'
        );

        $this->filesystem->expects(self::at(0))
            ->method('exists')
            ->with('/data/tests')
            ->willReturn(false);

        $this->filesystem->expects(self::once())
            ->method('mkdir')
            ->with('/data/tests');

        $this->filesystem->expects(self::at(2))
            ->method('exists')
            ->with('/data/tests/UserTest.php')
            ->willReturn(true);

        $this->filesystem->expects(self::never())
            ->method('dumpFile');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test class already exists at /data/tests/UserTest.php');

        $this->psr4TestClassWriter->write($generatedTestClass);
    }

    protected function setUp() : void
    {
        $this->configuration = (new ConfigurationBuilder())
            ->setAutoloadingStrategy(AutoloadingStrategy::PSR4)
            ->setSourceNamespace('App')
            ->setSourceDir('/data/lib')
            ->setTestsNamespace('App\Tests')
            ->setTestsDir('/data/tests')
            ->build();

        $this->filesystem = $this->createMock(Filesystem::class);

        $this->psr4TestClassWriter = new Psr4TestClassWriter(
            $this->configuration,
            $this->filesystem
        );
    }
}
