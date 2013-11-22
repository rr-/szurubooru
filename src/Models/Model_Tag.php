<?php
class Model_Tag extends AbstractModel
{
	public static function getTableName()
	{
		return 'tag';
	}

	public static function getQueryBuilder()
	{
		return 'Model_Tag_Querybuilder';
	}



	public static function locate($key, $throw = true)
	{
		$tag = R::findOne(self::getTableName(), 'LOWER(name) = LOWER(?)', [$key]);
		if (!$tag)
		{
			if ($throw)
				throw new SimpleException('Invalid tag name "' . $key . '"');
			return null;
		}
		return $tag;
	}



	public static function removeUnused()
	{
		$dbQuery = R::$f
			->begin()
			->select('id, name')
			->from(self::getTableName())
			->where()
			->not()->exists()
			->open()
				->select('1')
				->from('post_tag')
				->where('post_tag.tag_id = tag.id')
			->close();
		$rows = $dbQuery->get();
		$entities = R::convertToBeans(self::getTableName(), $rows);
		R::trashAll($entities);
	}

	public static function insertOrUpdate($tags)
	{
		$dbTags = [];
		foreach ($tags as $tag)
		{
			$dbTag = self::locate($tag, false);
			if (!$dbTag)
			{
				$dbTag = R::dispense(self::getTableName());
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

	public function getPostCount()
	{
		if ($this->bean->getMeta('post_count'))
			return $this->bean->getMeta('post_count');
		return $this->bean->countShared('post');
	}
}
