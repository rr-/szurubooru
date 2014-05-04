<?php
class PostType extends Enum implements IValidatable
{
	const Image = 1;
	const Flash = 2;
	const Youtube = 3;
	const Video = 4;

	protected $type;

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
		return self::_toString($this->type);
	}

	public function validate()
	{
		if (!in_array($this->type, self::getAllConstants()))
			throw new SimpleException('Invalid post type "%s"', $this->type);
	}
}
