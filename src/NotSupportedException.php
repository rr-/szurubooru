<?php
namespace Szurubooru;

class NotSupportedException extends \BadMethodCallException
{
	public function __construct()
	{
		parent::__construct('Not supported');
	}
}
