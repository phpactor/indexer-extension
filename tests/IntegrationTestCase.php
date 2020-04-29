<?php

namespace Phpactor\Indexer\Tests;

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
use Phpactor\Filesystem\Adapter\Simple\SimpleFileListProvider;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Indexer\Extension\IndexerExtension;
use Phpactor\Container\Container;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\MappedFilesystemRegistry;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\WorseReflection\Reflector;
use Phpactor\TestUtils\Workspace;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Model\FileList;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryRepository;
use Phpactor\Indexer\Model\Index;
use Psr\Log\NullLogger;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Indexer\Adapter\Worse\WorseIndexBuilder;
use Phpactor\Indexer\Model\IndexBuilder;
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

    protected function createInMemoryIndex(): Index
    {
        $repository = new InMemoryRepository();
        return new InMemoryIndex($repository);
    }

    protected function createTestBuilder(Index $index): IndexBuilder
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


    protected function fileListProvider(): FileListProvider
    {
        $path = $this->workspace()->path('/project');
        $provider = new FilesystemFileListProvider(new SimpleFilesystem($path, new SimpleFileListProvider(FilePath::fromString($path), true)));
        return $provider;
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

    protected function container(array $config = []): Container
    {
        $key = serialize($config);
        static $container = [];
        
        if (isset($container[$key])) {
            return $container[$key];
        }

        $container[$key] = PhpactorContainer::fromExtensions([
            ConsoleExtension::class,
            IndexerExtension::class,
            FilePathResolverExtension::class,
            LoggingExtension::class,
            SourceCodeFilesystemExtension::class,
            WorseReflectionExtension::class,
            ClassToFileExtension::class,
            RpcExtension::class,
            ComposerAutoloaderExtension::class,
            ReferenceFinderExtension::class,
        ], 
            array_merge([
                FilePathResolverExtension::PARAM_APPLICATION_ROOT => __DIR__ . '/../',
                FilePathResolverExtension::PARAM_PROJECT_ROOT => $this->workspace()->path(),
                IndexerExtension::PARAM_INDEX_PATH => $this->workspace()->path('/cache'),
                LoggingExtension::PARAM_ENABLED=> true,
                LoggingExtension::PARAM_PATH=> 'php://stderr',
                WorseReflectionExtension::PARAM_ENABLE_CACHE=> false,
            ], $config)
        );

        return $container[$key];
    }
}
