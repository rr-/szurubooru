<?php
namespace Szurubooru\Services\ImageManipulation;

class ImagickImageManipulator implements IImageManipulator
{
    public function loadFromBuffer($source)
    {
        $image = new \Imagick();
        $image->readImageBlob($source);
        if ($image->getImageFormat() === 'GIF')
            $image = $image->coalesceImages();
        return $image;
    }

    public function getImageWidth($imageResource)
    {
        return $imageResource->getImageWidth();
    }

    public function getImageHeight($imageResource)
    {
        return $imageResource->getImageHeight();
    }

    public function resizeImage(&$imageResource, $width, $height)
    {
        $imageResource->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 0.9);
    }

    public function cropImage(&$imageResource, $width, $height, $originX, $originY)
    {
        $imageResource->cropImage($width, $height, $originX, $originY);
        $imageResource->setImagePage(0, 0, 0, 0);
    }

    public function saveToBuffer($imageResource, $format)
    {
        switch ($format)
        {
            case self::FORMAT_JPEG:
                $matte = new \Imagick();
                $matte->newImage($imageResource->getImageWidth(), $imageResource->getImageHeight(), 'white');
                $matte->compositeimage($imageResource, \Imagick::COMPOSITE_OVER, 0, 0);
                $imageResource = $matte;
                $imageResource->setImageFormat('jpeg');
                break;

            case self::FORMAT_PNG:
                $imageResource->setImageFormat('png');
                break;

            default:
                throw new \InvalidArgumentException('Not supported');
        }

        return $imageResource->getImageBlob();
    }
}
