<?php
namespace Szurubooru\Tests\Helpers;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Tests\AbstractTestCase;

final class InputReaderTest extends AbstractTestCase
{
	public function testDecodingBase64()
	{
		$inputReader = new InputReader();
		$actual = $inputReader->decodeBase64('data:text/plain,YXdlc29tZSBkb2c=');
		$expected = 'awesome dog';
		$this->assertEquals($expected, $actual);
	}

	public function testDecodingEmptyBase64()
	{
		$inputReader = new InputReader();
		$this->assertNull($inputReader->decodeBase64($inputReader->iDontEvenExist));
	}
}
