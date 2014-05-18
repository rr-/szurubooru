<?php
class PostType extends AbstractEnum implements IEnum, IValidatable
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
		switch ($this->type)
		{
			case self::Image: return 'image';
			case self::Flash: return 'flash';
			case self::Youtube: return 'youtube';
			case self::Video: return 'video';
		}
		return null;
	}

	public function validate()
	{
		if (!in_array($this->type, self::getAllConstants()))
			throw new SimpleException('Invalid post type "%s"', $this->type);
	}
}
