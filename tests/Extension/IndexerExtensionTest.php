<?php

namespace Phpactor\Indexer\Tests\Extension;

use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\Extension\ComposerAutoloader\ComposerAutoloaderExtension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\Extension\Rpc\Request;
use Phpactor\Extension\Rpc\RequestHandler;
use Phpactor\Extension\Rpc\Response\EchoResponse;
use Phpactor\Extension\Rpc\RpcExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\Indexer\Extension\IndexerExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Reflector;

class IndexerExtensionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $this->initProject();
    }

    public function testReturnsImplementationFinder()
    {
        $container = $this->container();
        $finder = $container->get(ReferenceFinderExtension::SERVICE_IMPLEMENTATION_FINDER);
        self::assertInstanceOf(IndexedImplementationFinder::class, $finder);
    }

    public function testBuildIndex()
    {
        $container = $this->container();
        $indexer = $container->get(Indexer::class);
        $this->assertInstanceOf(Indexer::class, $indexer);
        $indexer->getJob()->run();
    }

    public function testRpcHandler()
    {
        $container = $this->container();
        $handler = $container->get(RpcExtension::SERVICE_REQUEST_HANDLER);
        assert($handler instanceof RequestHandler);
        $request = Request::fromNameAndParameters('index', []);
        $response = $handler->handle($request);
        self::assertInstanceOf(EchoResponse::class, $response);
        self::assertRegExp('{Indexed [0-9]+ files}', $response->message());
    }

    public function testSourceLocator()
    {
        $this->initProject();

        $container = $this->container();
        $indexer = $container->get(Indexer::class);
        assert($indexer instanceof Indexer);
        $indexer->reset();
        $indexer->getJob()->run();
        $reflector = $container->get(WorseReflectionExtension::SERVICE_REFLECTOR);
        assert($reflector instanceof Reflector);
        $class = $reflector->reflectClass('ClassWithWrongName');
        self::assertInstanceOf(ReflectionClass::class, $class);
    }
}
