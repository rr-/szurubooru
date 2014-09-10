<?php
namespace Szurubooru\Tests\Services;

class FileServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testSaving()
	{
		$testDirectory = $this->createTestDirectory();
		$configMock = $this->mockConfig($testDirectory);
		$httpHelper = $this->mock( \Szurubooru\Helpers\HttpHelper::class);
		$fileService = new \Szurubooru\Services\FileService($configMock, $httpHelper);
		$input = 'data:text/plain,YXdlc29tZSBkb2c=';
		$fileService->saveFromBase64($input, 'dog.txt');
		$expected = 'awesome dog';
		$actual = file_get_contents($testDirectory . DIRECTORY_SEPARATOR . 'dog.txt');
		$this->assertEquals($expected, $actual);
	}
}
