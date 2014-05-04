<?php
class AccessRank extends Enum implements IValidatable
{
	const Anonymous = 0;
	const Registered = 1;
	const PowerUser = 2;
	const Moderator = 3;
	const Admin = 4;
	const Nobody = 5;

	protected $accessRank;

	public function __construct($accessRank)
	{
		$this->accessRank = $accessRank;
	}

	public function toInteger()
	{
		return $this->accessRank;
	}

	public function toString()
	{
		return self::_toString($this->accessRank);
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
		if (!in_array($this->accessRank, self::getAllConstants()))
			throw new SimpleException('Invalid access rank "%s"', $this->accessRank);
	}
}
