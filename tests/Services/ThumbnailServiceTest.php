<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator;
use Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator;
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
		$this->thumbnailGeneratorMock = $this->mock(SmartThumbnailGenerator::class);
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
			->expects($this->exactly(2))
			->method('load')
			->withConsecutive(
				['nope'],
				['thumbnails' . DIRECTORY_SEPARATOR . 'blank.png'])
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
				IThumbnailGenerator::CROP_OUTSIDE)
			->willReturn('generated thumbnail');

		$this->fileDaoMock
			->expects($this->once())
			->method('save')
			->with('thumbnails' . DIRECTORY_SEPARATOR . '100x100' . DIRECTORY_SEPARATOR . 'nope', 'generated thumbnail');

		$thumbnailService = $this->getThumbnailService();
		$thumbnailService->generate('nope', 100, 100);
	}

	public function testThumbnailGeneratingFail()
	{
		$this->configMock->set('misc/thumbnailCropStyle', 'outside');

		$this->fileDaoMock
			->expects($this->exactly(3))
			->method('load')
			->withConsecutive(
				['nope'],
				['thumbnails' . DIRECTORY_SEPARATOR . 'blank.png'],
				['thumbnails' . DIRECTORY_SEPARATOR . 'blank.png'])
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
				IThumbnailGenerator::CROP_OUTSIDE)
			->willReturn(null);

		$this->fileDaoMock
			->expects($this->once())
			->method('save')
			->with('thumbnails' . DIRECTORY_SEPARATOR . '100x100' . DIRECTORY_SEPARATOR . 'nope', 'content of blank thumbnail (2)');

		$thumbnailService = $this->getThumbnailService();
		$thumbnailService->generate('nope', 100, 100);
	}


	private function getThumbnailService()
	{
		return new ThumbnailService(
			$this->configMock,
			$this->fileDaoMock,
			$this->thumbnailGeneratorMock);
	}
}
