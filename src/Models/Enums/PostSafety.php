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
		return pow(2, $this->safety);
	}

	public function toString()
	{
		return self::_toString($this->safety);
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
