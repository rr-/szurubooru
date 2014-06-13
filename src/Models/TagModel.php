<?php
use \Chibi\Sql as Sql;

final class TagModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'tag';
	}

	protected static function saveSingle($tag)
	{
		$tag->validate();

		Core::getDatabase()->transaction(function() use ($tag)
		{
			self::forgeId($tag, 'tag');

			$stmt = Sql\Statements::update();
			$stmt->setTable('tag');
			$stmt->setColumn('name', new Sql\Binding($tag->getName()));
			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($tag->getId())));

			Core::getDatabase()->execute($stmt);
		});

		return $tag;
	}

	protected static function removeSingle($tag)
	{
		$binding = new Sql\Binding($tag->getId());

		$stmt = Sql\Statements::delete();
		$stmt->setTable('post_tag');
		$stmt->setCriterion(Sql\Functors::equals('tag_id', $binding));
		Core::getDatabase()->execute($stmt);

		$stmt = Sql\Statements::delete();
		$stmt->setTable('tag');
		$stmt->setCriterion(Sql\Functors::equals('id', $binding));
		Core::getDatabase()->execute($stmt);
	}

	public static function rename($sourceName, $targetName)
	{
		Core::getDatabase()->transaction(function() use ($sourceName, $targetName)
		{
			$sourceTag = self::getByName($sourceName);
			$targetTag = self::tryGetByName($targetName);

			if ($targetTag)
			{
				if ($sourceTag->getId() == $targetTag->getId())
					throw new SimpleException('Source and target tag are the same');

				throw new SimpleException('Target tag already exists');
			}

			$sourceTag->setName($targetName);
			self::save($sourceTag);
		});
	}

	public static function merge($sourceName, $targetName)
	{
		Core::getDatabase()->transaction(function() use ($sourceName, $targetName)
		{
			$sourceTag = self::getByName($sourceName);
			$targetTag = self::getByName($targetName);

			if ($sourceTag->getId() == $targetTag->getId())
				throw new SimpleException('Source and target tag are the same');

			$stmt = Sql\Statements::select();
			$stmt->setColumn('post.id');
			$stmt->setTable('post');
			$stmt->setCriterion(
				Sql\Functors::conjunction()
				->add(
					Sql\Functors::exists(
						Sql\Statements::select()
							->setTable('post_tag')
							->setCriterion(
								Sql\Functors::conjunction()
									->add(Sql\Functors::equals('post_tag.post_id', 'post.id'))
									->add(Sql\Functors::equals('post_tag.tag_id', new Sql\Binding($sourceTag->getId()))))))
				->add(
					Sql\Functors::negation(
					Sql\Functors::exists(
						Sql\Statements::select()
							->setTable('post_tag')
							->setCriterion(
								Sql\Functors::conjunction()
									->add(Sql\Functors::equals('post_tag.post_id', 'post.id'))
									->add(Sql\Functors::equals('post_tag.tag_id', new Sql\Binding($targetTag->getId()))))))));
			$rows = Core::getDatabase()->fetchAll($stmt);
			$postIds = array_map(function($row) { return $row['id']; }, $rows);

			self::remove($sourceTag);

			foreach ($postIds as $postId)
			{
				$stmt = Sql\Statements::insert();
				$stmt->setTable('post_tag');
				$stmt->setColumn('post_id', new Sql\Binding($postId));
				$stmt->setColumn('tag_id', new Sql\Binding($targetTag->getId()));
				Core::getDatabase()->execute($stmt);
			}
		});
	}


	public static function getAllByPostId($key)
	{
		$stmt = Sql\Statements::select();
		$stmt->setColumn('tag.*');
		$stmt->setTable('tag');
		$stmt->addInnerJoin('post_tag', Sql\Functors::equals('post_tag.tag_id', 'tag.id'));
		$stmt->setCriterion(Sql\Functors::equals('post_tag.post_id', new Sql\Binding($key)));

		$rows = Core::getDatabase()->fetchAll($stmt);
		return self::spawnFromDatabaseRows($rows);
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
		$stmt = Sql\Statements::select();
		$stmt->setColumn('tag.*');
		$stmt->setTable('tag');
		$stmt->setCriterion(Sql\Functors::noCase(Sql\Functors::equals('name', new Sql\Binding($key))));

		$row = Core::getDatabase()->fetchOne($stmt);
		return self::spawnFromDatabaseRow($row);
	}



	public static function removeUnused()
	{
		$stmt = Sql\Statements::delete()
			->setTable('tag')
			->setCriterion(
				Sql\Functors::negation(
					Sql\Functors::exists(
						Sql\Statements::select()
							->setTable('post_tag')
							->setCriterion(Sql\Functors::equals('post_tag.tag_id', 'tag.id')))));
		Core::getDatabase()->execute($stmt);
	}

	public static function spawnFromNames(array $tagNames)
	{
		$tags = [];
		foreach ($tagNames as $tagName)
		{
			$tag = self::tryGetByName($tagName);
			if (!$tag)
			{
				$tag = self::spawn();
				$tag->setName($tagName);
				self::save($tag);
			}
			$tags []= $tag;
		}
		return $tags;
	}
}
