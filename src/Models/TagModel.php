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

			$stmt = new SqlUpdateStatement();
			$stmt->setTable('tag');
			$stmt->setColumn('name', new SqlBinding($tag->name));
			$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($tag->id)));

			Database::exec($stmt);
		});
		return $tag->id;
	}

	public static function remove($tag)
	{
		$binding = new SqlBinding($tag->id);

		$stmt = new SqlDeleteStatement();
		$stmt->setTable('post_tag');
		$stmt->setCriterion(new SqlEqualsOperator('tag_id', $binding));
		Database::exec($stmt);

		$stmt = new SqlDeleteStatement();
		$stmt->setTable('tag');
		$stmt->setCriterion(new SqlEqualsOperator('id', $binding));
		Database::exec($stmt);
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

			$stmt = new SqlSelectStatement();
			$stmt->setColumn('post.id');
			$stmt->setTable('post');
			$stmt->setCriterion(
				(new SqlConjunction)
				->add(
					new SqlExistsOperator(
						(new SqlSelectStatement)
							->setTable('post_tag')
							->setCriterion(
								(new SqlConjunction)
									->add(new SqlEqualsOperator('post_tag.post_id', 'post.id'))
									->add(new SqlEqualsOperator('post_tag.tag_id', new SqlBinding($sourceTag->id))))))
				->add(
					new SqlNegationOperator(
					new SqlExistsOperator(
						(new SqlSelectStatement)
							->setTable('post_tag')
							->setCriterion(
								(new SqlConjunction)
									->add(new SqlEqualsOperator('post_tag.post_id', 'post.id'))
									->add(new SqlEqualsOperator('post_tag.tag_id', new SqlBinding($targetTag->id))))))));
			$rows = Database::fetchAll($stmt);
			$postIds = array_map(function($row) { return $row['id']; }, $rows);

			self::remove($sourceTag);

			foreach ($postIds as $postId)
			{
				$stmt = new SqlInsertStatement();
				$stmt->setTable('post_tag');
				$stmt->setColumn('post_id', new SqlBinding($postId));
				$stmt->setColumn('tag_id', new SqlBinding($targetTag->id));
				Database::exec($stmt);
			}
		});
	}


	public static function findAllByPostId($key)
	{
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('tag.*');
		$stmt->setTable('tag');
		$stmt->addInnerJoin('post_tag', new SqlEqualsOperator('post_tag.tag_id', 'tag.id'));
		$stmt->setCriterion(new SqlEqualsOperator('post_tag.post_id', new SqlBinding($key)));

		$rows = Database::fetchAll($stmt);
		if ($rows)
			return self::convertRows($rows);
		return [];
	}

	public static function findByName($key, $throw = true)
	{
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('tag.*');
		$stmt->setTable('tag');
		$stmt->setCriterion(new SqlNoCaseOperator(new SqlEqualsOperator('name', new SqlBinding($key))));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid tag name "' . $key . '"');
		return null;
	}



	public static function removeUnused()
	{
		$stmt = (new SqlDeleteStatement)
			->setTable('tag')
			->setCriterion(
				new SqlNegationOperator(
					new SqlExistsOperator(
						(new SqlSelectStatement)
							->setTable('post_tag')
							->setCriterion(new SqlEqualsOperator('post_tag.tag_id', 'tag.id')))));
		Database::exec($stmt);
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
