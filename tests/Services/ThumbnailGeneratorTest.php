<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Helpers\ProgramExecutor;
use Szurubooru\Injector;
use Szurubooru\Services\ImageManipulation\ImageManipulator;
use Szurubooru\Services\ThumbnailGenerators\FlashThumbnailGenerator;
use Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator;
use Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator;
use Szurubooru\Services\ThumbnailGenerators\VideoThumbnailGenerator;
use Szurubooru\Tests\AbstractTestCase;

final class ThumbnailGeneratorTest extends AbstractTestCase
{
	public function testFlashThumbnails()
	{
		if (!ProgramExecutor::isProgramAvailable(FlashThumbnailGenerator::PROGRAM_NAME_DUMP_GNASH)
			and !ProgramExecutor::isProgramAvailable(FlashThumbnailGenerator::PROGRAM_NAME_SWFRENDER))
		{
			$this->markTestSkipped('External software necessary to run this test is missing.');
		}

		$thumbnailGenerator = $this->getThumbnailGenerator();
		$imageManipulator = $this->getImageManipulator();

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('flash.swf'),
			150,
			150,
			IThumbnailGenerator::CROP_OUTSIDE);

		$image = $imageManipulator->loadFromBuffer($result);
		$this->assertEquals(150, $imageManipulator->getImageWidth($image));
		$this->assertEquals(150, $imageManipulator->getImageHeight($image));
	}

	public function testVideoThumbnails()
	{
		if (!ProgramExecutor::isProgramAvailable(VideoThumbnailGenerator::PROGRAM_NAME_FFMPEG)
			and !ProgramExecutor::isProgramAvailable(VideoThumbnailGenerator::PROGRAM_NAME_FFMPEGTHUMBNAILER))
		{
			$this->markTestSkipped('External software necessary to run this test is missing.');
		}

		$thumbnailGenerator = $this->getThumbnailGenerator();
		$imageManipulator = $this->getImageManipulator();

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('video.mp4'),
			150,
			150,
			IThumbnailGenerator::CROP_OUTSIDE);

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
			IThumbnailGenerator::CROP_OUTSIDE);

		$image = $imageManipulator->loadFromBuffer($result);
		$this->assertEquals(150, $imageManipulator->getImageWidth($image));
		$this->assertEquals(150, $imageManipulator->getImageHeight($image));

		$result = $thumbnailGenerator->generate(
			$this->getTestFile('image.jpg'),
			150,
			150,
			IThumbnailGenerator::CROP_INSIDE);

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
			IThumbnailGenerator::CROP_OUTSIDE);

		$this->assertNull($result);
	}

	public function getImageManipulator()
	{
		return Injector::get(ImageManipulator::class);
	}

	public function getThumbnailGenerator()
	{
		return Injector::get(SmartThumbnailGenerator::class);
	}
}
