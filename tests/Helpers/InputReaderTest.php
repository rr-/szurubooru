<?php
namespace Szurubooru\Tests\Helpers;

class InputReaderTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testDecodingBase64()
	{
		$inputReader = new \Szurubooru\Helpers\InputReader();
		$actual = $inputReader->decodeBase64('data:text/plain,YXdlc29tZSBkb2c=');
		$expected = 'awesome dog';
		$this->assertEquals($expected, $actual);
	}
}
