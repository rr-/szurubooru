<?php
namespace Szurubooru\Services;
use Szurubooru\Services\ImageManipulation\ImageManipulator;

class ThumbnailGenerator
{
    const CROP_OUTSIDE = 0;
    const CROP_INSIDE = 1;

    private $imageManipulator;
    private $imageConverter;

    public function __construct(ImageManipulator $imageManipulator, ImageConverter $imageConverter)
    {
        $this->imageManipulator = $imageManipulator;
        $this->imageConverter = $imageConverter;
    }

    public function generate($source, $width, $height, $cropStyle, $format)
    {
        $image = $this->imageConverter->createImageFromBuffer($source);
        if (!$image)
            throw new \Exception('Error while loading supplied image');

        $srcWidth = $this->imageManipulator->getImageWidth($image);
        $srcHeight = $this->imageManipulator->getImageHeight($image);

        switch ($cropStyle)
        {
            case ThumbnailGenerator::CROP_OUTSIDE:
                $this->cropOutside($image, $srcWidth, $srcHeight, $width, $height);
                break;

            case ThumbnailGenerator::CROP_INSIDE:
                $this->cropInside($image, $srcWidth, $srcHeight, $width, $height);
                break;

            default:
                throw new \InvalidArgumentException('Unknown thumbnail crop style');
        }

        return $this->imageManipulator->saveToBuffer($image, $format);
    }

    private function cropOutside(&$image, $srcWidth, $srcHeight, $dstWidth, $dstHeight)
    {
        if (($dstHeight / $dstWidth) > ($srcHeight / $srcWidth))
        {
            $cropHeight = $dstHeight;
            $cropWidth = $dstHeight * $srcWidth / $srcHeight;
        }
        else
        {
            $cropWidth = $dstWidth;
            $cropHeight = $dstWidth * $srcHeight / $srcWidth;
        }
        $cropX = ($cropWidth - $dstWidth) >> 1;
        $cropY = ($cropHeight - $dstHeight) >> 1;

        $this->imageManipulator->resizeImage($image, $cropWidth, $cropHeight);
        $this->imageManipulator->cropImage($image, $dstWidth, $dstHeight, $cropX, $cropY);
    }

    private function cropInside(&$image, $srcWidth, $srcHeight, $dstWidth, $dstHeight)
    {
        if (($dstHeight / $dstWidth) < ($srcHeight / $srcWidth))
        {
            $cropHeight = $dstHeight;
            $cropWidth = $dstHeight * $srcWidth / $srcHeight;
        }
        else
        {
            $cropWidth = $dstWidth;
            $cropHeight = $dstWidth * $srcHeight / $srcWidth;
        }

        $this->imageManipulator->resizeImage($image, $cropWidth, $cropHeight);
    }
}
