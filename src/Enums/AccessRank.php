<?php
class AccessRank extends AbstractEnum implements IEnum, IValidatable
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
		switch ($this->accessRank)
		{
			case self::Anonymous: return 'anonymous';
			case self::Registered: return 'registered';
			case self::PowerUser: return 'power-user';
			case self::Moderator: return 'moderator';
			case self::Admin: return 'admin';
			case self::Nobody: return 'nobody';
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
		if (!in_array($this->accessRank, self::getAllConstants()))
			throw new SimpleException('Invalid access rank "%s"', $this->accessRank);
	}
}
