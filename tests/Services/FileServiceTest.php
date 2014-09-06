<?php
namespace Szurubooru\Tests\Services;

class FileServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testSaving()
	{
		$httpHelper = $this->mock( \Szurubooru\Helpers\HttpHelper::class);
		$fileService = new \Szurubooru\Services\FileService($this->getTestDirectory(), $httpHelper);
		$input = 'data:text/plain,YXdlc29tZSBkb2c=';
		$fileService->saveFromBase64($input, 'dog.txt');
		$expected = 'awesome dog';
		$actual = file_get_contents($this->getTestDirectory() . DIRECTORY_SEPARATOR . 'dog.txt');
		$this->assertEquals($expected, $actual);
	}
}
