<?php
class PostSafety
{
	const Safe = 1;
	const Sketchy = 2;
	const Unsafe = 3;

	public static function getAll()
	{
		return [self::Safe, self::Sketchy, self::Unsafe];
	}
}
