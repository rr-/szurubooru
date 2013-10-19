<?php
class Model_Post extends RedBean_SimpleModel
{
	public static function locate($key, $disallowNumeric = false)
	{
		if (is_numeric($key) and !$disallowNumeric)
		{
			$post = R::findOne('post', 'id = ?', [$key]);
			if (!$post)
				throw new SimpleException('Invalid post ID "' . $key . '"');
		}
		else
		{
			$post = R::findOne('post', 'name = ?', [$key]);
			if (!$post)
				throw new SimpleException('Invalid post name "' . $key . '"');
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

	public static function validateTag($tag)
	{
		$tag = trim($tag);

		$minLength = 1;
		$maxLength = 64;
		if (strlen($tag) < $minLength)
			throw new SimpleException('Tag must have at least ' . $minLength . ' characters');
		if (strlen($tag) > $maxLength)
			throw new SimpleException('Tag must have at most ' . $maxLength . ' characters');

		if (!preg_match('/^[a-zA-Z0-9_-]+$/i', $tag))
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
