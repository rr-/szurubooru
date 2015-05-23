<?php
namespace Szurubooru;

class NotSupportedException extends \BadMethodCallException
{
	public function __construct($message = null)
	{
		parent::__construct($message === null ? 'Not supported' : $message);
	}
}
