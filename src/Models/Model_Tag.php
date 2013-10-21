<?php
class Model_Tag extends RedBean_SimpleModel
{
	public static function locate($key, $throw = true)
	{
		$tag = R::findOne('tag', 'LOWER(name) = LOWER(?)', [$key]);
		if (!$tag)
		{
			if ($throw)
				throw new SimpleException('Invalid tag name "' . $key . '"');
			return null;
		}
		return $tag;
	}

	public static function insertOrUpdate($tags)
	{
		$dbTags = [];
		foreach ($tags as $tag)
		{
			$dbTag = self::locate($tag, false);
			if (!$dbTag)
			{
				$dbTag = R::dispense('tag');
				$dbTag->name = $tag;
				R::store($dbTag);
			}
			$dbTags []= $dbTag;
		}
		return $dbTags;
	}

	public static function validateTag($tag)
	{
		$tag = trim($tag);

		$minLength = 1;
		$maxLength = 64;
		if (strlen($tag) < $minLength)
			throw new SimpleException('Tag must have at least ' . $minLength . ' characters');
		if (strlen($tag) > $maxLength)
			throw new SimpleException('Tag must have at most ' . $maxLength . ' characters');

		if (!preg_match('/^[a-zA-Z0-9_.-]+$/i', $tag))
			throw new SimpleException('Invalid tag "' . $tag . '"');

		if (preg_match('/^\.\.?$/', $tag))
			throw new SimpleException('Invalid tag "' . $tag . '"');

		return $tag;
	}

	public static function validateTags($tags)
	{
		$tags = trim($tags);
		$tags = preg_split('/[,;\s]+/', $tags);
		$tags = array_filter($tags, function($x) { return $x != ''; });
		$tags = array_unique($tags);

		foreach ($tags as $key => $tag)
			$tags[$key] = self::validateTag($tag);

		if (empty($tags))
			throw new SimpleException('No tags set');

		return $tags;
	}
}
