<?php

namespace Phpactor\ProjectQuery\Tests\Integration\Extension;

use PHPUnit\Framework\TestCase;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\Extension\ComposerAutoloader\ComposerAutoloaderExtension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\ProjectQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\ProjectQuery\Extension\ProjectQueryExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Container\PhpactorContainer;

class ProjectQueryExtensionTest extends TestCase
{
    public function testReturnsImplementationFinder()
    {
        $container = $this->createContainer();
        $finder = $container->get(ReferenceFinderExtension::SERVICE_IMPLEMENTATION_FINDER);
        self::assertInstanceOf(IndexedImplementationFinder::class, $finder);
        }

        private function createContainer()
    {
        return PhpactorContainer::fromExtensions([
            ConsoleExtension::class,
            ProjectQueryExtension::class,
            FilePathResolverExtension::class,
            LoggingExtension::class,
            SourceCodeFilesystemExtension::class,
            WorseReflectionExtension::class,
            ClassToFileExtension::class,
            ComposerAutoloaderExtension::class,
            ReferenceFinderExtension::class,
        ], [
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__ . '/../../..',
            ProjectQueryExtension::PARAM_INDEX_PATH => __DIR__ . '/../../../cache',
            LoggingExtension::PARAM_PATH => 'php://stderr',
            LoggingExtension::PARAM_ENABLED => true,
            LoggingExtension::PARAM_LEVEL => 'debug',
        ]);
    }
    }
