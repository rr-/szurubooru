<?php
namespace Szurubooru\Tests;

class TestHelper
{
    public static function createTestDirectory()
    {
        $path = self::getTestDirectoryPath();
        if (!file_exists($path))
            mkdir($path, 0777, true);
        return $path;
    }

    public static function mockConfig($dataPath = null, $publicDataPath = null)
    {
        return new ConfigMock($dataPath, $publicDataPath);
    }

    public static function getTestFile($fileName)
    {
        return file_get_contents(self::getTestFilePath($fileName));
    }

    public static function getTestFilePath($fileName)
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'test_files' . DIRECTORY_SEPARATOR . $fileName;
    }

    public static function cleanTestDirectory()
    {
        if (!file_exists(self::getTestDirectoryPath()))
            return;

        $dirIterator = new \RecursiveDirectoryIterator(
            self::getTestDirectoryPath(),
            \RecursiveDirectoryIterator::SKIP_DOTS);

        $files = new \RecursiveIteratorIterator(
            $dirIterator,
            \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $fileInfo)
        {
            if ($fileInfo->isDir())
                rmdir($fileInfo->getRealPath());
            else
                unlink($fileInfo->getRealPath());
        }
    }

    private static function getTestDirectoryPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'files';
    }
}
