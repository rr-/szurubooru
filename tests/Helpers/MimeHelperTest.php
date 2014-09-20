<?php
namespace Szurubooru\Tests\Helpers;

class MimeHelperTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testGettingMime()
	{
		$expected = 'image/jpeg';
		$actual = \Szurubooru\Helpers\MimeHelper::getMimeTypeFromBuffer($this->getTestFile('image.jpg'));
		$this->assertEquals($expected, $actual);
	}

	public function testIsFlash()
	{
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isFlash('application/x-shockwave-flash'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isFlash('APPLICATION/X-SHOCKWAVE-FLASH'));
		$this->assertFalse(\Szurubooru\Helpers\MimeHelper::isFlash('something else'));
	}

	public function testIsImage()
	{
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isImage('IMAGE/JPEG'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isImage('IMAGE/PNG'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isImage('IMAGE/GIF'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isImage('image/jpeg'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isImage('image/png'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isImage('image/gif'));
		$this->assertFalse(\Szurubooru\Helpers\MimeHelper::isImage('something else'));
	}

	public function testIsVideo()
	{
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isVideo('VIDEO/MP4'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isVideo('video/mp4'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isVideo('APPLICATION/OGG'));
		$this->assertTrue(\Szurubooru\Helpers\MimeHelper::isVideo('application/ogg'));
		$this->assertFalse(\Szurubooru\Helpers\MimeHelper::isVideo('something else'));
	}
}
