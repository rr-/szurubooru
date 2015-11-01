<?php
namespace Szurubooru\Services;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Helpers\ProgramExecutor;
use Szurubooru\Services\ImageManipulation\ImageManipulator;

class ImageConverter
{
    const PROGRAM_NAME_DUMP_GNASH = 'dump-gnash';
    const PROGRAM_NAME_SWFRENDER = 'swfrender';
    const PROGRAM_NAME_FFMPEG = 'ffmpeg';
    const PROGRAM_NAME_FFMPEGTHUMBNAILER = 'ffmpegthumbnailer';

    private $imageManipulator;

    public function __construct(ImageManipulator $imageManipulator)
    {
        $this->imageManipulator = $imageManipulator;
    }

    public function createImageFromBuffer($source)
    {
        $tmpSourcePath = tempnam(sys_get_temp_dir(), 'thumb') . '.dat';
        $tmpTargetPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';
        try
        {
            file_put_contents($tmpSourcePath, $source);
            $this->convert($tmpSourcePath, $tmpTargetPath);

            $tmpSource = file_get_contents($tmpTargetPath);
            return $this->imageManipulator->loadFromBuffer($tmpSource);
        }
        finally
        {
            $this->deleteIfExists($tmpSourcePath);
            $this->deleteIfExists($tmpTargetPath);
        }
    }

    public function convert($sourcePath, $targetPath)
    {
        $mime = MimeHelper::getMimeTypeFromFile($sourcePath);

        if (MimeHelper::isImage($mime))
            copy($sourcePath, $targetPath);

        elseif (MimeHelper::isFlash($mime))
            $this->convertFromFlash($sourcePath, $targetPath);

        elseif (MimeHelper::isVideo($mime))
            $this->convertFromVideo($sourcePath, $targetPath);

        else
            throw new \Exception('Error while converting file to image - unrecognized MIME: ' . $mime);
    }

    private function convertFromFlash($sourcePath, $targetPath)
    {
        if (ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_DUMP_GNASH))
        {
            ProgramExecutor::run(
                self::PROGRAM_NAME_DUMP_GNASH,
                [
                    '--screenshot', 'last',
                    '--screenshot-file', $targetPath,
                    '-1',
                    '-r1',
                    '--max-advances', '15',
                    $sourcePath,
                ]);
        }

        if (!file_exists($targetPath) && ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_SWFRENDER))
        {
            ProgramExecutor::run(
                self::PROGRAM_NAME_SWFRENDER,
                [
                    'swfrender',
                    $sourcePath,
                    '-o',
                    $targetPath,
                ]);
        }

        if (!file_exists($targetPath) && ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEG))
        {
            ProgramExecutor::run(
                self::PROGRAM_NAME_FFMPEG,
                [
                    '-i',
                    $sourcePath,
                    '-vframes', '1',
                    $targetPath,
                ]);
        }

        if (!file_exists($targetPath))
            throw new \Exception('Error while converting Flash file to image');
    }

    private function convertFromVideo($sourcePath, $targetPath)
    {
        if (ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEGTHUMBNAILER))
        {
            ProgramExecutor::run(
                self::PROGRAM_NAME_FFMPEGTHUMBNAILER,
                [
                    '-i' . $sourcePath,
                    '-o' . $targetPath,
                    '-s0',
                    '-t12%%'
                ]);
        }

        if (!file_exists($targetPath) && ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEG))
        {
            ProgramExecutor::run(self::PROGRAM_NAME_FFMPEG,
                [
                    '-i', $sourcePath,
                    '-vframes', '1',
                    $targetPath
                ]);
        }

        if (!file_exists($targetPath))
            throw new \Exception('Error while converting video file to image');
    }

    private function deleteIfExists($path)
    {
        if (file_exists($path))
            unlink($path);
    }
}
