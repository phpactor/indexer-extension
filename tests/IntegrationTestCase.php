<?php

namespace Phpactor\WorkspaceQuery\Tests;

use PHPUnit\Framework\TestCase;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\Extension\ComposerAutoloader\ComposerAutoloaderExtension;
use Phpactor\Extension\Rpc\RpcExtension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Container\PhpactorContainer;
use Phpactor\WorkspaceQuery\Extension\WorkspaceQueryExtension;
use Phpactor\Container\Container;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\MappedFilesystemRegistry;
use Phpactor\WorseReflection\Reflector;
use Phpactor\TestUtils\Workspace;
use Phpactor\WorkspaceQuery\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\WorkspaceQuery\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\WorkspaceQuery\Model\FileList;
use Phpactor\WorkspaceQuery\Adapter\Php\InMemory\InMemoryRepository;
use Phpactor\WorkspaceQuery\Model\Index;
use Psr\Log\NullLogger;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\WorkspaceQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Symfony\Component\Process\Process;

class IntegrationTestCase extends TestCase
{
    protected function workspace(): Workspace
    {
        return Workspace::create(__DIR__ . '/Workspace');
    }

    protected function initProject(): void
    {
        $this->workspace()->loadManifest((string)file_get_contents(__DIR__ . '/Integration/Manifest/buildIndex.php.test'));
        $process = new Process([
            'composer', 'install'
        ], $this->workspace()->path('/'));
        $process->mustRun();
    }

    protected function createBuilder(Index $index): IndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $index,
            ReflectorBuilder::create()->addLocator(
                new StubSourceLocator(
                    ReflectorBuilder::create()->build(),
                    $this->workspace()->path('/'),
                    $this->workspace()->path('/')
                )
            )->build(),
            new NullLogger()
        );
        return $indexBuilder;
    }

    protected function createInMemoryIndex(): Index
    {
        $repository = new InMemoryRepository();
        return new InMemoryIndex($repository);
    }

    protected function fileList(Index $index): FileList
    {
        $provider = new FilesystemFileListProvider(new MappedFilesystemRegistry([
            'foobar' => new SimpleFilesystem($this->workspace()->path('/project')),
        ]), 'foobar');
        return $provider->provideFileList($index);
    }

    protected function createReflector(): Reflector
    {
        return ReflectorBuilder::create()->addLocator(
            new StubSourceLocator(
                ReflectorBuilder::create()->build(),
                $this->workspace()->path('/'),
                $this->workspace()->path('/')
            )
        )->build();
    }

    protected function container(): Container
    {
        static $container = null;
        
        if ($container) {
            return $container;
        }

        $container = PhpactorContainer::fromExtensions([
            ConsoleExtension::class,
            WorkspaceQueryExtension::class,
            FilePathResolverExtension::class,
            LoggingExtension::class,
            SourceCodeFilesystemExtension::class,
            WorseReflectionExtension::class,
            ClassToFileExtension::class,
            RpcExtension::class,
            ComposerAutoloaderExtension::class,
            ReferenceFinderExtension::class,
        ], [
            FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__ . '/../',
            FilePathResolverExtension::PARAM_PROJECT_ROOT => $this->workspace()->path(),
            WorkspaceQueryExtension::PARAM_INDEX_PATH => $this->workspace()->path('/cache'),
            LoggingExtension::PARAM_ENABLED=> true,
            LoggingExtension::PARAM_PATH=> 'php://stderr',
            WorseReflectionExtension::PARAM_ENABLE_CACHE=> false,
        ]);

        return $container;
    }
}
