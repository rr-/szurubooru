<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Services\ImageManipulation\GdImageManipulator;
use Szurubooru\Services\ImageManipulation\IImageManipulator;
use Szurubooru\Services\ImageManipulation\ImageManipulator;
use Szurubooru\Services\ImageManipulation\ImagickImageManipulator;
use Szurubooru\Tests\AbstractTestCase;

final class ImageManipulatorTest extends AbstractTestCase
{
    public static function imageManipulatorProvider()
    {
        $manipulators = [];
        $manipulators[] = self::getImagickImageManipulator();
        $manipulators[] = self::getGdImageManipulator();
        $manipulators[] = self::getAutoImageManipulator();
        return array_map(function($manipulator)
            {
                return [$manipulator];
            }, array_filter($manipulators));
    }

    public function testImagickAvailability()
    {
        if (!self::getImagickImageManipulator())
            $this->markTestSkipped('Imagick is not installed');
        $this->assertTrue(true);
    }

    public function testGdAvailability()
    {
        if (!self::getGdImageManipulator())
            $this->markTestSkipped('Gd is not installed');
        $this->assertTrue(true);
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testImageSize($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $this->assertEquals(640, $imageManipulator->getImageWidth($image));
        $this->assertEquals(480, $imageManipulator->getImageHeight($image));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testNonImage($imageManipulator)
    {
        $this->assertNull($imageManipulator->loadFromBuffer($this->getTestFile('flash.swf')));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testImageResizing($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $imageManipulator->resizeImage($image, 400, 500);
        $this->assertEquals(400, $imageManipulator->getImageWidth($image));
        $this->assertEquals(500, $imageManipulator->getImageHeight($image));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testImageCroppingBleedWidth($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $imageManipulator->cropImage($image, 640, 480, 200, 200);
        $this->assertEquals(440, $imageManipulator->getImageWidth($image));
        $this->assertEquals(280, $imageManipulator->getImageHeight($image));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testImageCroppingBleedPosition($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $imageManipulator->cropImage($image, 640, 480, -200, -200);
        $this->assertEquals(440, $imageManipulator->getImageWidth($image));
        $this->assertEquals(280, $imageManipulator->getImageHeight($image));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testImageCroppingBleedBoth($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $imageManipulator->cropImage($image, 642, 481, -1, -1);
        $this->assertEquals(640, $imageManipulator->getImageWidth($image));
        $this->assertEquals(480, $imageManipulator->getImageHeight($image));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testImageCroppingMaxBleeding($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $imageManipulator->cropImage($image, 100, 100, 1000, 1000);
        $this->assertEquals(1, $imageManipulator->getImageWidth($image));
        $this->assertEquals(1, $imageManipulator->getImageHeight($image));
    }

    /**
     * @dataProvider imageManipulatorProvider
     */
    public function testSaving($imageManipulator)
    {
        $image = $imageManipulator->loadFromBuffer($this->getTestFile('image.jpg'));
        $jpegBuffer = $imageManipulator->saveToBuffer($image, IImageManipulator::FORMAT_JPEG);
        $pngBuffer = $imageManipulator->saveToBuffer($image, IImageManipulator::FORMAT_PNG);
        $this->assertEquals('image/jpeg', MimeHelper::getMimeTypeFromBuffer($jpegBuffer));
        $this->assertEquals('image/png', MimeHelper::getMimeTypeFromBuffer($pngBuffer));
    }

    private static function getImagickImageManipulator()
    {
        if (extension_loaded('imagick'))
            return new ImagickImageManipulator();
        else
            return null;
    }

    private static function getGdImageManipulator()
    {
        if (extension_loaded('gd'))
            return new GdImageManipulator();
        else
            return null;
    }

    private static function getAutoImageManipulator()
    {
        if (extension_loaded('gd') && extension_loaded('imagick'))
        {
            return new ImageManipulator(
                self::getImagickImageManipulator(),
                self::getGdImageManipulator());
        }
        return null;
    }
}
