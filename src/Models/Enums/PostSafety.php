<?php
class PostSafety extends Enum implements IValidatable
{
	const Safe = 1;
	const Sketchy = 2;
	const Unsafe = 3;

	protected $safety;

	public function __construct($safety)
	{
		$this->safety = $safety;
	}

	public function toInteger()
	{
		return $this->safety;
	}

	public function toFlag()
	{
		return pow(2, $this->safety - 1);
	}

	public function toString()
	{
		switch ($this->safety)
		{
			case self::Safe: return 'safe';
			case self::Sketchy: return 'sketchy';
			case self::Unsafe: return 'unsafe';
		}
		return null;
	}

	public static function makeFlags($safetyCodes)
	{
		if (!is_array($safetyCodes))
			return 0;

		$flags = 0;
		foreach (self::getAll() as $safety)
			if (in_array($safety->toInteger(), $safetyCodes))
				$flags |= $safety->toFlag();
		return $flags;
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
		if (!in_array($this->safety, self::getAllConstants()))
			throw new SimpleException('Invalid safety type "%s"', $this->safety);
	}
}
