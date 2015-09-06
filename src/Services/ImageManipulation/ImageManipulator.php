<?php
namespace Szurubooru\Services\ImageManipulation;
use Szurubooru\Services\ImageManipulation\GdImageManipulator;
use Szurubooru\Services\ImageManipulation\ImagickImageManipulator;
use Szurubooru\Config;

class ImageManipulator implements IImageManipulator
{
    private $strategy;

    public function __construct(
        ImagickImageManipulator $imagickImageManipulator,
        GdImageManipulator $gdImageManipulator,
        Config $config)
    {
        if ($config->misc->imageExtension === 'imagick')
        {
            if (!extension_loaded('imagick'))
                throw new \RuntimeException('Plugin set to imagick, but not enabled in PHP');
            $this->strategy = $imagickImageManipulator;
        }
        else if ($config->misc->imageExtension === 'gd')
        {
            if (!extension_loaded('gd'))
                throw new \RuntimeException('Plugin set to gd, but not enabled in PHP');
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
