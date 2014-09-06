<?php
namespace Szurubooru\Helpers;

final class InputReader
{
	public function __construct()
	{
		$_PUT = [];
		if (isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD'] == 'PUT')
			parse_str(file_get_contents('php://input'), $_PUT);

		foreach ([$_GET, $_POST, $_PUT] as $source)
		{
			foreach ($source as $key => $value)
				$this->$key = $value;
		}
	}

	public function __get($key)
	{
		return null;
	}
}
