<?php
class PostSafety extends Enum
{
	const Safe = 1;
	const Sketchy = 2;
	const Unsafe = 3;

	public static function toFlag($safety)
	{
		return pow(2, $safety);
	}
}
