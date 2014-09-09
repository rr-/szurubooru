<?php
namespace Szurubooru\Tests\Services;

class ThumbnailServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testDeleteUsedThumbnails()
	{
		define('DS', DIRECTORY_SEPARATOR);

		$tempDirectory = $this->createTestDirectory();
		mkdir($tempDirectory . DS . 'thumbnails');
		mkdir($tempDirectory . DS . 'thumbnails' . DS . '5x5');
		mkdir($tempDirectory . DS . 'thumbnails' . DS . '10x10');
		touch($tempDirectory . DS . 'thumbnails' . DS . '5x5' . DS . 'remove');
		touch($tempDirectory . DS . 'thumbnails' . DS . '5x5' . DS . 'keep');
		touch($tempDirectory . DS . 'thumbnails' . DS . '10x10' . DS . 'remove');

		$httpHelperMock = $this->mock(\Szurubooru\Helpers\HttpHelper::class);
		$fileService = new \Szurubooru\Services\FileService($tempDirectory, $httpHelperMock);
		$thumbnailGeneratorMock = $this->mock(\Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator::class);

		$thumbnailService = new \Szurubooru\Services\ThumbnailService($fileService, $thumbnailGeneratorMock);
		$thumbnailService->deleteUsedThumbnails('remove');

		$this->assertFalse(file_exists($tempDirectory . DS . 'thumbnails' . DS . '5x5' . DS . 'remove'));
		$this->assertTrue(file_exists($tempDirectory . DS . 'thumbnails' . DS . '5x5' . DS . 'keep'));
		$this->assertFalse(file_exists($tempDirectory . DS . 'thumbnails' . DS . '10x10' . DS . 'remove'));
	}

	public function testGetUsedThumbnailSizes()
	{
		$tempDirectory = $this->createTestDirectory();
		mkdir($tempDirectory . DIRECTORY_SEPARATOR . '5x5');
		mkdir($tempDirectory . DIRECTORY_SEPARATOR . '10x10');
		mkdir($tempDirectory . DIRECTORY_SEPARATOR . 'something unexpected');
		touch($tempDirectory . DIRECTORY_SEPARATOR . '15x15');

		$fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$fileServiceMock->expects($this->once())->method('getFullPath')->willReturn($tempDirectory);
		$thumbnailGeneratorMock = $this->mock(\Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator::class);

		$thumbnailService = new \Szurubooru\Services\ThumbnailService($fileServiceMock, $thumbnailGeneratorMock);

		$expected = [[5, 5], [10, 10]];
		$actual = iterator_to_array($thumbnailService->getUsedThumbnailSizes());

		$this->assertEquals(count($expected), count($actual));
		foreach ($expected as $v)
			$this->assertContains($v, $actual);
	}
}
