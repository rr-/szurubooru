<?php
namespace Szurubooru\Tests\Services;

class ThumbnailServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;
	private $fileServiceMock;
	private $thumbnailGeneratorMock;

	public function setUp()
	{
		parent::setUp();

		$this->configMock = $this->mockConfig();
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
		$this->thumbnailGeneratorMock = $this->mock(\Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator::class);
	}

	public function testGetUsedThumbnailSizes()
	{
		$tempDirectory = $this->createTestDirectory();
		mkdir($tempDirectory . DIRECTORY_SEPARATOR . '5x5');
		mkdir($tempDirectory . DIRECTORY_SEPARATOR . '10x10');
		mkdir($tempDirectory . DIRECTORY_SEPARATOR . 'something unexpected');
		touch($tempDirectory . DIRECTORY_SEPARATOR . '15x15');

		$this->fileServiceMock->expects($this->once())->method('getFullPath')->with('thumbnails')->willReturn($tempDirectory);
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

		$this->fileServiceMock->expects($this->once())->method('getFullPath')->with('thumbnails')->willReturn($tempDirectory);
		$this->fileServiceMock->expects($this->exactly(2))->method('delete')->withConsecutive(
			['thumbnails' . DIRECTORY_SEPARATOR . '10x10' . DIRECTORY_SEPARATOR . 'remove'],
			['thumbnails' . DIRECTORY_SEPARATOR . '5x5' . DIRECTORY_SEPARATOR . 'remove']);
		$thumbnailService = $this->getThumbnailService();

		$thumbnailService->deleteUsedThumbnails('remove');
	}

	public function testGeneratingFromNonExistingSource()
	{
		$this->configMock->set('misc/thumbnailCropStyle', 'outside');

		$this->fileServiceMock
			->expects($this->exactly(2))
			->method('load')
			->withConsecutive(
				['nope'],
				['thumbnails/blank.png'])
			->will(
				$this->onConsecutiveCalls(
					null,
					'content of blank thumbnail'));

		$this->thumbnailGeneratorMock
			->expects($this->once())
			->method('generate')
			->with(
				'content of blank thumbnail',
				100,
				100,
				\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE)
			->willReturn('generated thumbnail');

		$this->fileServiceMock
			->expects($this->once())
			->method('save')
			->with('thumbnails/100x100/nope', 'generated thumbnail');

		$thumbnailService = $this->getThumbnailService();
		$thumbnailService->generate('nope', 100, 100);
	}

	public function testThumbnailGeneratingFail()
	{
		$this->configMock->set('misc/thumbnailCropStyle', 'outside');

		$this->fileServiceMock
			->expects($this->exactly(3))
			->method('load')
			->withConsecutive(
				['nope'],
				['thumbnails/blank.png'],
				['thumbnails/blank.png'])
			->will(
				$this->onConsecutiveCalls(
					null,
					'content of blank thumbnail',
					'content of blank thumbnail (2)'));

		$this->thumbnailGeneratorMock
			->expects($this->once())
			->method('generate')
			->with(
				'content of blank thumbnail',
				100,
				100,
				\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE)
			->willReturn(null);

		$this->fileServiceMock
			->expects($this->once())
			->method('save')
			->with('thumbnails/100x100/nope', 'content of blank thumbnail (2)');

		$thumbnailService = $this->getThumbnailService();
		$thumbnailService->generate('nope', 100, 100);
	}


	private function getThumbnailService()
	{
		return new \Szurubooru\Services\ThumbnailService(
			$this->configMock,
			$this->fileServiceMock,
			$this->thumbnailGeneratorMock);
	}
}
