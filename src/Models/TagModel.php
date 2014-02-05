<?php
class TagModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'tag';
	}

	public static function save($tag)
	{
		Database::transaction(function() use ($tag)
		{
			self::forgeId($tag, 'tag');

			$query = (new SqlQuery)
				->update('tag')
				->set('name = ?')->put($tag->name)
				->where('id = ?')->put($tag->id);

			Database::query($query);
		});
		return $tag->id;
	}

	public static function remove($tag)
	{
		$query = (new SqlQuery)
			->deleteFrom('post_tag')
			->where('tag_id = ?')->put($tag->id);
		Database::query($query);

		$query = (new SqlQuery)
			->deleteFrom('tag')
			->where('id = ?')->put($tag->id);
		Database::query($query);
	}

	public static function rename($sourceName, $targetName)
	{
		Database::transaction(function() use ($sourceName, $targetName)
		{
			$sourceTag = TagModel::findByName($sourceName);
			$targetTag = TagModel::findByName($targetName, false);

			if ($targetTag and $targetTag->id != $sourceTag->id)
				throw new SimpleException('Target tag already exists');

			$sourceTag->name = $targetName;
			self::save($sourceTag);
		});
	}

	public static function merge($sourceName, $targetName)
	{
		Database::transaction(function() use ($sourceName, $targetName)
		{
			$sourceTag = TagModel::findByName($sourceName);
			$targetTag = TagModel::findByName($targetName);

			if ($sourceTag->id == $targetTag->id)
				throw new SimpleException('Source and target tag are the same');

			$query = (new SqlQuery)
				->select('post.id')
				->from('post')
				->where()
					->exists()
					->open()
						->select('1')
						->from('post_tag')
						->where('post_tag.post_id = post.id')
						->and('post_tag.tag_id = ?')->put($sourceTag->id)
					->close()
				->and()
					->not()->exists()
					->open()
						->select('1')
						->from('post_tag')
						->where('post_tag.post_id = post.id')
						->and('post_tag.tag_id = ?')->put($targetTag->id)
					->close();
			$rows = Database::fetchAll($query);
			$postIds = array_map(function($row) { return $row['id']; }, $rows);

			self::remove($sourceTag);

			foreach ($postIds as $postId)
			{
				$query = (new SqlQuery)
					->insertInto('post_tag')
					->surround('post_id, tag_id')
					->values()->surround('?, ?')
					->put([$postId, $targetTag->id]);
				Database::query($query);
			}
		});
	}


	public static function findAllByPostId($key)
	{
		$query = new SqlQuery();
		$query
			->select('tag.*')
			->from('tag')
			->innerJoin('post_tag')
			->on('post_tag.tag_id = tag.id')
			->where('post_tag.post_id = ?')
			->put($key);

		$rows = Database::fetchAll($query);
		if ($rows)
			return self::convertRows($rows);
		return [];
	}

	public static function findByName($key, $throw = true)
	{
		$query = (new SqlQuery)
			->select('*')
			->from('tag')
			->where('LOWER(name) = LOWER(?)')->put($key);

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid tag name "' . $key . '"');
		return null;
	}



	public static function removeUnused()
	{
		$query = (new SqlQuery)
			->deleteFrom('tag')
			->where()
			->not()->exists()
			->open()
				->select('1')
				->from('post_tag')
				->where('post_tag.tag_id = tag.id')
			->close();
		Database::query($query);
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

		if (!preg_match('/^[()\[\]a-zA-Z0-9_.-]+$/i', $tag))
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
