<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

final class TagModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'tag';
	}

	public static function save($tag)
	{
		$tag->validate();

		Database::transaction(function() use ($tag)
		{
			self::forgeId($tag, 'tag');

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('tag');
			$stmt->setColumn('name', new Sql\Binding($tag->getName()));
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($tag->getId())));

			Database::exec($stmt);
		});

		return $tag;
	}

	public static function remove($tag)
	{
		$binding = new Sql\Binding($tag->getId());

		$stmt = new Sql\DeleteStatement();
		$stmt->setTable('post_tag');
		$stmt->setCriterion(new Sql\EqualsFunctor('tag_id', $binding));
		Database::exec($stmt);

		$stmt = new Sql\DeleteStatement();
		$stmt->setTable('tag');
		$stmt->setCriterion(new Sql\EqualsFunctor('id', $binding));
		Database::exec($stmt);
	}

	public static function rename($sourceName, $targetName)
	{
		Database::transaction(function() use ($sourceName, $targetName)
		{
			$sourceTag = TagModel::getByName($sourceName);
			$targetTag = TagModel::tryGetByName($targetName);

			if ($targetTag and $targetTag->getId() != $sourceTag->getId())
				throw new SimpleException('Target tag already exists');

			$sourceTag->setName($targetName);
			TagModel::validateTag($sourceTag->getName());
			self::save($sourceTag);
		});
	}

	public static function merge($sourceName, $targetName)
	{
		Database::transaction(function() use ($sourceName, $targetName)
		{
			$sourceTag = TagModel::getByName($sourceName);
			$targetTag = TagModel::getByName($targetName);

			if ($sourceTag->getId() == $targetTag->getId())
				throw new SimpleException('Source and target tag are the same');

			$stmt = new Sql\SelectStatement();
			$stmt->setColumn('post.id');
			$stmt->setTable('post');
			$stmt->setCriterion(
				(new Sql\ConjunctionFunctor)
				->add(
					new Sql\ExistsFunctor(
						(new Sql\SelectStatement)
							->setTable('post_tag')
							->setCriterion(
								(new Sql\ConjunctionFunctor)
									->add(new Sql\EqualsFunctor('post_tag.post_id', 'post.id'))
									->add(new Sql\EqualsFunctor('post_tag.tag_id', new Sql\Binding($sourceTag->getId()))))))
				->add(
					new Sql\NegationFunctor(
					new Sql\ExistsFunctor(
						(new Sql\SelectStatement)
							->setTable('post_tag')
							->setCriterion(
								(new Sql\ConjunctionFunctor)
									->add(new Sql\EqualsFunctor('post_tag.post_id', 'post.id'))
									->add(new Sql\EqualsFunctor('post_tag.tag_id', new Sql\Binding($targetTag->getId()))))))));
			$rows = Database::fetchAll($stmt);
			$postIds = array_map(function($row) { return $row['id']; }, $rows);

			self::remove($sourceTag);

			foreach ($postIds as $postId)
			{
				$stmt = new Sql\InsertStatement();
				$stmt->setTable('post_tag');
				$stmt->setColumn('post_id', new Sql\Binding($postId));
				$stmt->setColumn('tag_id', new Sql\Binding($targetTag->getId()));
				Database::exec($stmt);
			}
		});
	}


	public static function getAllByPostId($key)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('tag.*');
		$stmt->setTable('tag');
		$stmt->addInnerJoin('post_tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'));
		$stmt->setCriterion(new Sql\EqualsFunctor('post_tag.post_id', new Sql\Binding($key)));

		$rows = Database::fetchAll($stmt);
		if ($rows)
			return self::spawnFromDatabaseRows($rows);
		return [];
	}

	public static function getByName($key)
	{
		$ret = self::tryGetByName($key);
		if (!$ret)
			throw new SimpleNotFoundException('Invalid tag name "%s"', $key);
		return $ret;
	}

	public static function tryGetByName($key)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('tag.*');
		$stmt->setTable('tag');
		$stmt->setCriterion(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('name', new Sql\Binding($key))));

		$row = Database::fetchOne($stmt);
		return $row
			? self::spawnFromDatabaseRow($row)
			: null;
	}



	public static function removeUnused()
	{
		$stmt = (new Sql\DeleteStatement)
			->setTable('tag')
			->setCriterion(
				new Sql\NegationFunctor(
					new Sql\ExistsFunctor(
						(new Sql\SelectStatement)
							->setTable('post_tag')
							->setCriterion(new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id')))));
		Database::exec($stmt);
	}

	public static function spawnFromNames(array $tagNames)
	{
		$tags = [];
		foreach ($tagNames as $tagName)
		{
			$tag = TagModel::tryGetByName($tagName);
			if (!$tag)
			{
				$tag = TagModel::spawn();
				$tag->setName($tagName);
				TagModel::save($tag);
			}
			$tags []= $tag;
		}
		return $tags;
	}



	public static function validateTags($tags)
	{
		foreach ($tags as $key => $tag)
			$tags[$key] = self::validateTag($tag);

		return $tags;
	}
}
