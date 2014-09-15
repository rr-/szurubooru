<?php
namespace Szurubooru\Tests\Services;

class FileServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testSaving()
	{
		$testDirectory = $this->createTestDirectory();
		$configMock = $this->mockConfig($testDirectory);
		$httpHelper = $this->mock(\Szurubooru\Helpers\HttpHelper::class);
		$fileService = new \Szurubooru\Services\FileService($configMock, $httpHelper);
		$fileService->save('dog.txt', 'awesome dog');
		$expected = 'awesome dog';
		$actual = file_get_contents($testDirectory . DIRECTORY_SEPARATOR . 'dog.txt');
		$this->assertEquals($expected, $actual);
	}

	public function testDownload()
	{
		$configMock = $this->mockConfig();
		$httpHelper = $this->mock(\Szurubooru\Helpers\HttpHelper::class);
		$fileService = new \Szurubooru\Services\FileService($configMock, $httpHelper);
		$content = $fileService->download('http://modernseoul.files.wordpress.com/2012/04/korean-alphabet-chart-modern-seoul.jpg');
		$this->assertGreaterThan(0, strlen($content));
	}
}
