<?php
class SqlBinding
{
	protected $content;
	protected $name;
	private static $bindingCount = 0;

	public function __construct($content)
	{
		$this->content = $content;
		$this->name = ':p' . (self::$bindingCount ++);
	}

	public function getName()
	{
		return $this->name;
	}

	public function getValue()
	{
		return $this->content;
	}

	public static function fromArray(array $contents)
	{
		return array_map(function($content)
		{
			return new SqlBinding($content);
		}, $contents);
	}
}
