<?php
namespace Szurubooru\Tests\Helpers;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Tests\AbstractTestCase;

final class MimeHelperTest extends AbstractTestCase
{
	public static function animatedGifProvider()
	{
		return
		[
			['test_files/video.mp4', false],
			['test_files/static.gif', false],
			['test_files/animated.gif', true],
			['test_files/animated2.gif', true],
			['test_files/animated3.gif', true],
			['test_files/animated4.gif', true],
		];
	}

	public function testGettingMime()
	{
		$expected = 'image/jpeg';
		$actual = MimeHelper::getMimeTypeFromBuffer($this->getTestFile('image.jpg'));
		$this->assertEquals($expected, $actual);
	}

	public function testIsFlash()
	{
		$this->assertTrue(MimeHelper::isFlash('application/x-shockwave-flash'));
		$this->assertTrue(MimeHelper::isFlash('APPLICATION/X-SHOCKWAVE-FLASH'));
		$this->assertFalse(MimeHelper::isFlash('something else'));
	}

	public function testIsImage()
	{
		$this->assertTrue(MimeHelper::isImage('IMAGE/JPEG'));
		$this->assertTrue(MimeHelper::isImage('IMAGE/PNG'));
		$this->assertTrue(MimeHelper::isImage('IMAGE/GIF'));
		$this->assertTrue(MimeHelper::isImage('image/jpeg'));
		$this->assertTrue(MimeHelper::isImage('image/png'));
		$this->assertTrue(MimeHelper::isImage('image/gif'));
		$this->assertFalse(MimeHelper::isImage('something else'));
	}

	public function testIsVideo()
	{
		$this->assertTrue(MimeHelper::isVideo('VIDEO/MP4'));
		$this->assertTrue(MimeHelper::isVideo('video/mp4'));
		$this->assertTrue(MimeHelper::isVideo('APPLICATION/OGG'));
		$this->assertTrue(MimeHelper::isVideo('application/ogg'));
		$this->assertFalse(MimeHelper::isVideo('something else'));
	}

	/**
	 * @dataProvider animatedGifProvider
	 */
	public function testIsAnimatedGif($path, $expected)
	{
		$fullPath = __DIR__
			. DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . $path;
		$actual = MimeHelper::isBufferAnimatedGif(file_get_contents($fullPath));
		$this->assertEquals($expected, $actual);
	}
}
