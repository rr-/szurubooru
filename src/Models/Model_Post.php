<?php
class Model_Post extends AbstractModel
{
	public static function locate($key, $disallowNumeric = false, $throw = true)
	{
		if (is_numeric($key) and !$disallowNumeric)
		{
			$post = R::findOne(self::getTableName(), 'id = ?', [$key]);
			if (!$post)
			{
				if ($throw)
					throw new SimpleException('Invalid post ID "' . $key . '"');
				return null;
			}
		}
		else
		{
			$post = R::findOne(self::getTableName(), 'name = ?', [$key]);
			if (!$post)
			{
				if ($throw)
					throw new SimpleException('Invalid post name "' . $key . '"');
				return null;
			}
		}
		return $post;
	}

	public static function validateSafety($safety)
	{
		$safety = intval($safety);

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleException('Invalid safety type "' . $safety . '"');

		return $safety;
	}

	public static function validateSource($source)
	{
		$source = trim($source);

		$maxLength = 200;
		if (strlen($source) > $maxLength)
			throw new SimpleException('Source must have at most ' . $maxLength . ' characters');

		return $source;
	}

	public static function getTableName()
	{
		return 'post';
	}

	public static function getQueryBuilder()
	{
		return 'Model_Post_QueryBuilder';
	}

	public function isTaggedWith($tagName)
	{
		$tagName = trim(strtolower($tagName));
		foreach ($this->sharedTag as $tag)
			if (trim(strtolower($tag->name)) == $tagName)
				return true;
		return false;
	}
}
