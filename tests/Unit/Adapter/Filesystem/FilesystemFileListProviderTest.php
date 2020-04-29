<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Filesystem;

use PHPUnit\Framework\TestCase;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\Filesystem\Domain\FilesystemRegistry;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Tests\IntegrationTestCase;

class FilesystemFileListProviderTest extends IntegrationTestCase
{
    /**
     * @var FilesystemFileListProvider
     */
    private $provider;

    /**
     * @var ObjectProphecy
     */
    private $filesystem;

    /**
     * @var ObjectProphecy
     */
    private $index;


    protected function setUp(): void
    {
        $this->filesystem = new SimpleFilesystem($this->workspace()->path());
        $this->provider = new FilesystemFileListProvider($this->filesystem);
        $this->workspace()->reset();
        $this->index = $this->prophesize(Index::class);
    }

    public function testProvidesSingleFile()
    {
        $this->workspace()->put('foo.php', '<?php echo "hello";');
        $index = new InMemoryIndex();
        $list = $this->provider->provideFileList($index, $this->workspace()->path('foo.php'));
        self::assertEquals(1, $list->count());
    }

    public function testProvidesFromFilesystemRoot()
    {
        $this->workspace()->put('foo.php', '<?php echo "hello";');
        $this->workspace()->put('bar.php', '<?php echo "hello";');
        $index = new InMemoryIndex();

        $list = $this->provider->provideFileList($index);

        self::assertEquals(2, $list->count());
    }

    public function testDoesNotUseCacheIfSubPathProvided()
    {
        $this->workspace()->put('foo.php', '<?php echo "hello";');

        $this->index->isFresh()->shouldNotBeCalled();

        $list = $this->provider->provideFileList($this->index->reveal(), $this->workspace()->path());

        self::assertEquals(1, $list->count());
    }
}
