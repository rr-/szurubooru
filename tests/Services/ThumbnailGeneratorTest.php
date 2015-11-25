<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Helpers\ProgramExecutor;
use Szurubooru\Injector;
use Szurubooru\Services\ImageConverter;
use Szurubooru\Services\ImageManipulation\IImageManipulator;
use Szurubooru\Services\ImageManipulation\ImageManipulator;
use Szurubooru\Services\ThumbnailGenerator;
use Szurubooru\Tests\AbstractTestCase;

final class ThumbnailGeneratorTest extends AbstractTestCase
{
    public function testFlashThumbnails()
    {
        if (!ProgramExecutor::isProgramAvailable(ImageConverter::PROGRAM_NAME_DUMP_GNASH)
            and !ProgramExecutor::isProgramAvailable(ImageConverter::PROGRAM_NAME_SWFRENDER))
        {
            $this->markTestSkipped('External software necessary to run this test is missing.');
        }

        $thumbnailGenerator = $this->getThumbnailGenerator();
        $imageManipulator = $this->getImageManipulator();

        $result = $thumbnailGenerator->generate(
            $this->getTestFile('flash.swf'),
            150,
            150,
            ThumbnailGenerator::CROP_OUTSIDE,
            IImageManipulator::FORMAT_PNG);

        $image = $imageManipulator->loadFromBuffer($result);
        $this->assertEquals(150, $imageManipulator->getImageWidth($image));
        $this->assertEquals(150, $imageManipulator->getImageHeight($image));
    }

    public function testVideoThumbnails()
    {
        if (!ProgramExecutor::isProgramAvailable(ImageConverter::PROGRAM_NAME_FFMPEG)
            and !ProgramExecutor::isProgramAvailable(ImageConverter::PROGRAM_NAME_FFMPEGTHUMBNAILER))
        {
            $this->markTestSkipped('External software necessary to run this test is missing.');
        }

        $thumbnailGenerator = $this->getThumbnailGenerator();
        $imageManipulator = $this->getImageManipulator();

        $result = $thumbnailGenerator->generate(
            $this->getTestFile('video.mp4'),
            150,
            150,
            ThumbnailGenerator::CROP_OUTSIDE,
            IImageManipulator::FORMAT_PNG);

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
            ThumbnailGenerator::CROP_OUTSIDE,
            IImageManipulator::FORMAT_PNG);

        $image = $imageManipulator->loadFromBuffer($result);
        $this->assertEquals(150, $imageManipulator->getImageWidth($image));
        $this->assertEquals(150, $imageManipulator->getImageHeight($image));

        $result = $thumbnailGenerator->generate(
            $this->getTestFile('image.jpg'),
            150,
            150,
            ThumbnailGenerator::CROP_INSIDE,
            IImageManipulator::FORMAT_PNG);

        $image = $imageManipulator->loadFromBuffer($result);
        $this->assertEquals(150, $imageManipulator->getImageWidth($image));
        $this->assertEquals(112, $imageManipulator->getImageHeight($image));
    }

    public function testBadThumbnails()
    {
        $thumbnailGenerator = $this->getThumbnailGenerator();
        $imageManipulator = $this->getImageManipulator();

        $this->setExpectedException(\Exception::class);
        $thumbnailGenerator->generate(
            $this->getTestFile('text.txt'),
            150,
            150,
            ThumbnailGenerator::CROP_OUTSIDE,
            IImageManipulator::FORMAT_PNG);
    }

    public function getImageManipulator()
    {
        return Injector::get(ImageManipulator::class);
    }

    public function getThumbnailGenerator()
    {
        return Injector::get(ThumbnailGenerator::class);
    }
}
