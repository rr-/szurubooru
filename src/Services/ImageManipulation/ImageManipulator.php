<?php
namespace Szurubooru\Services\ImageManipulation;
use Szurubooru\Services\ImageManipulation\GdImageManipulator;
use Szurubooru\Services\ImageManipulation\ImagickImageManipulator;

class ImageManipulator implements IImageManipulator
{
    private $strategy;

    public function __construct(
        ImagickImageManipulator $imagickImageManipulator,
        GdImageManipulator $gdImageManipulator)
    {
        if (extension_loaded('imagick'))
        {
            $this->strategy = $imagickImageManipulator;
        }
        else if (extension_loaded('gd'))
        {
            $this->strategy = $gdImageManipulator;
        }
        else
        {
            throw new \RuntimeException('Neither imagick or gd image extensions are enabled');
        }
    }

    public function loadFromBuffer($source)
    {
        return $this->strategy->loadFromBuffer($source);
    }

    public function getImageWidth($imageResource)
    {
        return $this->strategy->getImageWidth($imageResource);
    }

    public function getImageHeight($imageResource)
    {
        return $this->strategy->getImageHeight($imageResource);
    }

    public function resizeImage(&$imageResource, $width, $height)
    {
        return $this->strategy->resizeImage($imageResource, $width, $height);
    }

    public function cropImage(&$imageResource, $width, $height, $originX, $originY)
    {
        return $this->strategy->cropImage($imageResource, $width, $height, $originX, $originY);
    }

    public function saveToBuffer($source, $format)
    {
        return $this->strategy->saveToBuffer($source, $format);
    }
}
