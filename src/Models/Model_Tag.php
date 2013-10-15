<?php
class Model_Tag extends RedBean_SimpleModel
{
	public static function insertOrUpdate($tags)
	{
		$dbTags = [];
		foreach ($tags as $tag)
		{
			$dbTag = R::findOne('tag', 'name = ?', [$tag]);
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
}
