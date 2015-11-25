<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Services\ThumbnailGenerator;
use Szurubooru\Services\ThumbnailService;
use Szurubooru\Tests\AbstractTestCase;

final class ThumbnailServiceTest extends AbstractTestCase
{
    private $configMock;
    private $fileDaoMock;
    private $thumbnailGeneratorMock;

    public function setUp()
    {
        parent::setUp();

        $this->configMock = $this->mockConfig();
        $this->fileDaoMock = $this->mock(PublicFileDao::class);
        $this->thumbnailServiceMock = $this->mock(ThumbnailService::class);
        $this->thumbnailGeneratorMock = $this->mock(ThumbnailGenerator::class);
    }

    public function testGetUsedThumbnailSizes()
    {
        $tempDirectory = $this->createTestDirectory();
        mkdir($tempDirectory . DIRECTORY_SEPARATOR . '5x5');
        mkdir($tempDirectory . DIRECTORY_SEPARATOR . '10x10');
        mkdir($tempDirectory . DIRECTORY_SEPARATOR . 'something unexpected');
        touch($tempDirectory . DIRECTORY_SEPARATOR . '15x15');

        $this->fileDaoMock->expects($this->once())->method('getFullPath')->with('thumbnails')->willReturn($tempDirectory);
        $thumbnailService = $this->getThumbnailService();

        $expected = [[5, 5], [10, 10]];
        $actual = iterator_to_array($thumbnailService->getUsedThumbnailSizes());

        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $v)
            $this->assertContains($v, $actual);
    }

    public function testDeleteUsedThumbnails()
    {
        $tempDirectory = $this->createTestDirectory();
        mkdir($tempDirectory . DIRECTORY_SEPARATOR . '5x5');
        mkdir($tempDirectory . DIRECTORY_SEPARATOR . '10x10');
        touch($tempDirectory . DIRECTORY_SEPARATOR . '5x5' . DIRECTORY_SEPARATOR . 'remove');
        touch($tempDirectory . DIRECTORY_SEPARATOR . '5x5' . DIRECTORY_SEPARATOR . 'keep');
        touch($tempDirectory . DIRECTORY_SEPARATOR . '10x10' . DIRECTORY_SEPARATOR . 'remove');

        $this->fileDaoMock->expects($this->once())->method('getFullPath')->with('thumbnails')->willReturn($tempDirectory);
        $this->fileDaoMock->expects($this->exactly(2))->method('delete')->withConsecutive(
            ['thumbnails' . DIRECTORY_SEPARATOR . '10x10' . DIRECTORY_SEPARATOR . 'remove'],
            ['thumbnails' . DIRECTORY_SEPARATOR . '5x5' . DIRECTORY_SEPARATOR . 'remove']);
        $thumbnailService = $this->getThumbnailService();

        $thumbnailService->deleteUsedThumbnails('remove');
    }

    public function testGeneratingFromNonExistingSource()
    {
        $this->configMock->set('misc/thumbnailCropStyle', 'outside');

        $this->fileDaoMock
            ->expects($this->once())
            ->method('load')
            ->with('nope')
            ->willReturn(null);

        $this->thumbnailGeneratorMock
            ->expects($this->never())
            ->method('generate');

        $this->fileDaoMock
            ->expects($this->never())
            ->method('save');

        $thumbnailService = $this->getThumbnailService();
        $this->assertEquals(
            'thumbnails' . DIRECTORY_SEPARATOR . 'blank.png',
            $thumbnailService->generate('nope', 100, 100));
    }

    public function testThumbnailGeneratingFail()
    {
        $this->configMock->set('misc/thumbnailCropStyle', 'outside');

        $this->fileDaoMock
            ->expects($this->once())
            ->method('load')
            ->with('nope')
            ->willReturn('content of file');

        $this->thumbnailGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'content of file',
                100,
                100,
                ThumbnailGenerator::CROP_OUTSIDE)
            ->willReturn(null);

        $this->fileDaoMock
            ->expects($this->never())
            ->method('save');

        $thumbnailService = $this->getThumbnailService();
        $this->assertEquals(
            'thumbnails' . DIRECTORY_SEPARATOR . 'blank.png',
            $thumbnailService->generate('nope', 100, 100));
    }

    public function testThumbnailGeneratingSuccess()
    {
        $this->configMock->set('misc/thumbnailCropStyle', 'outside');

        $this->fileDaoMock
            ->expects($this->once())
            ->method('load')
            ->with('okay')
            ->willReturn('content of file');

        $this->thumbnailGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'content of file',
                100,
                100,
                ThumbnailGenerator::CROP_OUTSIDE)
            ->willReturn('content of thumbnail');

        $this->fileDaoMock
            ->expects($this->once())
            ->method('save')
            ->with(
                'thumbnails' . DIRECTORY_SEPARATOR . '100x100' . DIRECTORY_SEPARATOR . 'okay',
                'content of thumbnail');

        $thumbnailService = $this->getThumbnailService();
        $this->assertEquals(
            'thumbnails' . DIRECTORY_SEPARATOR . '100x100' . DIRECTORY_SEPARATOR . 'okay',
            $thumbnailService->generate('okay', 100, 100));
    }

    private function getThumbnailService()
    {
        return new ThumbnailService(
            $this->configMock,
            $this->fileDaoMock,
            $this->thumbnailGeneratorMock);
    }
}
