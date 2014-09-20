<?php
namespace Szurubooru\Tests\Services;

class ImageManipulatorTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $imageManipulators;

	public function setUp()
	{
		parent::setUp();

		$imagickImageManipulator = new \Szurubooru\Services\ImageManipulation\ImagickImageManipulator();
		$gdImageManipulator = new \Szurubooru\Services\ImageManipulation\GdImageManipulator();
		$autoImageManipulator = new \Szurubooru\Services\ImageManipulation\ImageManipulator(
			$imagickImageManipulator,
			$gdImageManipulator);

		$this->imageManipulators = [
			$imagickImageManipulator,
			$gdImageManipulator,
			$autoImageManipulator,
		];
	}

	public function testImageSize()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$this->assertEquals(640, $imageManipulator->getImageWidth($image));
			$this->assertEquals(480, $imageManipulator->getImageHeight($image));
		}
	}

	public function testNonImage()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$this->assertNull($imageManipulator->loadFromBuffer($this->getTestFile('flash.swf')));
		}
	}

	public function testImageResizing()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$imageManipulator->resizeImage($image, 400, 500);
			$this->assertEquals(400, $imageManipulator->getImageWidth($image));
			$this->assertEquals(500, $imageManipulator->getImageHeight($image));
		}
	}

	public function testImageCroppingBleedWidth()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$imageManipulator->cropImage($image, 640, 480, 200, 200);
			$this->assertEquals(440, $imageManipulator->getImageWidth($image));
			$this->assertEquals(280, $imageManipulator->getImageHeight($image));
		}
	}

	public function testImageCroppingBleedPosition()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$imageManipulator->cropImage($image, 640, 480, -200, -200);
			$this->assertEquals(440, $imageManipulator->getImageWidth($image));
			$this->assertEquals(280, $imageManipulator->getImageHeight($image));
		}
	}

	public function testImageCroppingBleedBoth()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$imageManipulator->cropImage($image, 642, 481, -1, -1);
			$this->assertEquals(640, $imageManipulator->getImageWidth($image));
			$this->assertEquals(480, $imageManipulator->getImageHeight($image));
		}
	}

	public function testImageCroppingMaxBleeding()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$imageManipulator->cropImage($image, 100, 100, 1000, 1000);
			$this->assertEquals(1, $imageManipulator->getImageWidth($image));
			$this->assertEquals(1, $imageManipulator->getImageHeight($image));
		}
	}

	public function testSaving()
	{
		foreach ($this->getImageManipulators() as $imageManipulator)
		{
			$image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
			$jpegBuffer = $imageManipulator->saveToBuffer($image, \Szurubooru\Services\ImageManipulation\IImageManipulator::FORMAT_JPEG);
			$pngBuffer = $imageManipulator->saveToBuffer($image, \Szurubooru\Services\ImageManipulation\IImageManipulator::FORMAT_PNG);
			$this->assertEquals('image/jpeg', \Szurubooru\Helpers\MimeHelper::getMimeTypeFromBuffer($jpegBuffer));
			$this->assertEquals('image/png', \Szurubooru\Helpers\MimeHelper::getMimeTypeFromBuffer($pngBuffer));
		}
	}

	private function getImageManipulators()
	{
		return $this->imageManipulators;
	}
}
