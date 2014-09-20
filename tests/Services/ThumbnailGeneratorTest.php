<?php
namespace Szurubooru\Tests\Services;

class ThumbnailGeneratorTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testFlashThumbnails()
	{
		if (!\Szurubooru\Helpers\ProgramExecutor::isProgramAvailable(\Szurubooru\Services\ThumbnailGenerators\FlashThumbnailGenerator::PROGRAM_NAME_DUMP_GNASH)
			and !\Szurubooru\Helpers\ProgramExecutor::isProgramAvailable(\Szurubooru\Services\ThumbnailGenerators\FlashThumbnailGenerator::PROGRAM_NAME_SWFRENDER))
		{
			$this->markTestSkipped('External software necessary to run this test is missing.');
		}

		$thumbnailGenerator = $this->getThumbnailGenerator();
		$imageManipulator = $this->getImageManipulator();

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('flash.swf'),
			150,
			150,
			\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE);

		$image = $imageManipulator->loadFromBuffer($result);
		$this->assertEquals(150, $imageManipulator->getImageWidth($image));
		$this->assertEquals(150, $imageManipulator->getImageHeight($image));
	}

	public function testVideoThumbnails()
	{
		if (!\Szurubooru\Helpers\ProgramExecutor::isProgramAvailable(\Szurubooru\Services\ThumbnailGenerators\VideoThumbnailGenerator::PROGRAM_NAME_FFMPEG)
			and !\Szurubooru\Helpers\ProgramExecutor::isProgramAvailable(\Szurubooru\Services\ThumbnailGenerators\VideoThumbnailGenerator::PROGRAM_NAME_FFMPEGTHUMBNAILER))
		{
			$this->markTestSkipped('External software necessary to run this test is missing.');
		}

		$thumbnailGenerator = $this->getThumbnailGenerator();
		$imageManipulator = $this->getImageManipulator();

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('video.mp4'),
			150,
			150,
			\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE);

		$image = $imageManipulator->loadFromBuffer($result);
		$this->assertEquals(150, $imageManipulator->getImageWidth($image));
		$this->assertEquals(150, $imageManipulator->getImageHeight($image));
	}

	public function testImageThumbnails()
	{
		$thumbnailGenerator = $this->getThumbnailGenerator();
		$imageManipulator = $this->getImageManipulator();

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('image.jpg'),
			150,
			150,
			\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE);

		$image = $imageManipulator->loadFromBuffer($result);
		$this->assertEquals(150, $imageManipulator->getImageWidth($image));
		$this->assertEquals(150, $imageManipulator->getImageHeight($image));

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('image.jpg'),
			150,
			150,
			\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_INSIDE);

		$image = $imageManipulator->loadFromBuffer($result);
		$this->assertEquals(150, $imageManipulator->getImageWidth($image));
		$this->assertEquals(112, $imageManipulator->getImageHeight($image));
	}

	public function testBadThumbnails()
	{
		$thumbnailGenerator = $this->getThumbnailGenerator();
		$imageManipulator = $this->getImageManipulator();

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('text.txt'),
			150,
			150,
			\Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE);

		$this->assertNull($result);
	}

	public function getImageManipulator()
	{
		return \Szurubooru\Injector::get(\Szurubooru\Services\ImageManipulation\ImageManipulator::class);
	}

	public function getThumbnailGenerator()
	{
		return \Szurubooru\Injector::get(\Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator::class);
	}
}
