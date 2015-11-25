<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\IFileDao;

class FileDao implements IFileDao
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function load($fileName)
    {
        $fullPath = $this->getFullPath($fileName);
        return file_exists($fullPath)
            ? file_get_contents($fullPath)
            : null;
    }

    public function save($fileName, $data)
    {
        $fullPath = $this->getFullPath($fileName);
        $this->createFolders($fileName);
        file_put_contents($fullPath, $data);
    }

    public function delete($fileName)
    {
        $fullPath = $this->getFullPath($fileName);
        if (file_exists($fullPath))
            unlink($fullPath);
    }

    public function exists($fileName)
    {
        $fullPath = $this->getFullPath($fileName);
        return file_exists($fullPath);
    }

    public function getFullPath($fileName)
    {
        return $this->directory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function listAll()
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory));
        $files = [];
        foreach ($iterator as $path)
        {
            if (!$path->isDir())
                $files[] = $this->getRelativePath($this->directory, $path->getPathName());
        }
        return $files;
    }

    private function createFolders($fileName)
    {
        $fullPath = dirname($this->getFullPath($fileName));
        if (!file_exists($fullPath))
            mkdir($fullPath, 0777, true);
    }

    private function getRelativePath($from, $to)
    {
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
        $from = explode('/', str_replace('\\', '/', $from));
        $to = explode('/', str_replace('\\', '/', $to));
        $relPath = $to;
        foreach ($from as $depth => $dir)
        {
            if ($dir === $to[$depth])
            {
                array_shift($relPath);
            }
            else
            {
                $remaining = count($from) - $depth;
                if ($remaining > 1)
                {
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                }
                else
                {
                    $relPath[0] = $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }
}
