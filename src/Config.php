<?php
namespace Szurubooru;

class Config extends \ArrayObject
{
    private $dataDirectory;
    private $publicDataDirectory;

    public function __construct($dataDirectory, $publicDataDirectory)
    {
        $this->setFlags($this->getArrayObjectFlags());
        $this->dataDirectory = $dataDirectory;
        $this->publicDataDirectory = $publicDataDirectory;
        $this->tryLoadFromIni([
            $dataDirectory . DIRECTORY_SEPARATOR . 'config.ini',
            $dataDirectory . DIRECTORY_SEPARATOR . 'local.ini']);
    }

    public function tryLoadFromIni($configPaths)
    {
        if (!is_array($configPaths))
            $configPaths = [$configPaths];

        foreach ($configPaths as $configPath)
        {
            if (file_exists($configPath))
                $this->loadFromIni($configPath);
        }
    }

    public function getDataDirectory()
    {
        return $this->dataDirectory;
    }

    public function getPublicDataDirectory()
    {
        return $this->publicDataDirectory;
    }

    public function offsetGet($index)
    {
        if (!parent::offsetExists($index))
            return null;
        return parent::offsetGet($index);
    }

    public function loadFromIni($configPath)
    {
        $array = parse_ini_file($configPath, true, INI_SCANNER_RAW);

        foreach ($array as $key => $value)
        {
            if (!is_array($value))
            {
                $this->offsetSet($key, $value);
            }
            else
            {
                $section = $key;
                $ptr = $this;

                foreach (explode('.', $section) as $subSection)
                {
                    if (!$ptr->offsetExists($subSection))
                        $ptr->offsetSet($subSection, new \ArrayObject([], $this->getArrayObjectFlags()));

                    $ptr = $ptr->$subSection;
                }

                foreach ($value as $sectionKey => $sectionValue)
                    $ptr->offsetSet($sectionKey, $sectionValue);
            }
        }
    }

    private function getArrayObjectFlags()
    {
        return parent::ARRAY_AS_PROPS | parent::STD_PROP_LIST;
    }
}
