<?php
class UserAvatarStyle extends AbstractEnum implements IEnum, IValidatable
{
	const Gravatar = 1;
	const Custom = 2;
	const None = 3;

	private $type;

	public function __construct($type)
	{
		$this->type = $type;
	}

	public function toInteger()
	{
		return $this->type;
	}

	public function toString()
	{
		switch ($this->type)
		{
			case self::None: return 'none';
			case self::Gravatar: return 'gravatar';
			case self::Custom: return 'custom';
		}
		return null;
	}

	public static function getAll()
	{
		return array_map(function($constantName)
		{
			return new self($constantName);
		}, self::getAllConstants());
	}

	public function validate()
	{
		if (!in_array($this->type, self::getAllConstants()))
			throw new SimpleException('Invalid user picture type "%s"', $this->type);
	}
}
