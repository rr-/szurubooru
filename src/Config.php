<?php
namespace Szurubooru;

class Config extends \ArrayObject
{
	public function __construct(array $configPaths = [])
	{
		parent::setFlags(parent::ARRAY_AS_PROPS | parent::STD_PROP_LIST);

		foreach ($configPaths as $configPath)
		{
			if (file_exists($configPath))
				$this->loadFromIni($configPath);
		}
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
				$this->$key = $value;
			}
			else
			{
				$section = $key;
				$ptr = $this;

				foreach (explode('.', $section) as $subSection)
				{
					if (!$ptr->offsetExists($subSection))
						$ptr->offsetSet($subSection, new self());

					$ptr = $ptr->$subSection;
				}

				foreach ($value as $sectionKey => $sectionValue)
					$ptr->offsetSet($sectionKey, $sectionValue);
			}
		}
	}
}
