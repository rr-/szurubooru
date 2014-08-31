<?php
namespace Szurubooru\Services;

class TimeService
{
	public function __construct()
	{
		date_default_timezone_set('UTC');
	}

	public function getCurrentTime()
	{
		return date('c');
	}
}
