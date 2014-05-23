<?php
use \Chibi\Sql as Sql;

class TagSearchService extends AbstractSearchService
{
	public static function decorateCustom($stmt)
	{
		$stmt->addColumn(Sql\Functors::alias(Sql\Functors::count('post_tag.post_id'), 'post_count'));
	}

	public static function getRelatedTags($parentTagName)
	{
		$parentTagEntity = TagModel::tryGetByName($parentTagName);
		if (empty($parentTagEntity))
			return [];
		$parentTagId = $parentTagEntity->getId();

		$punishCommonTags = false;

		$rows = self::getSiblingTagsWithOccurences($parentTagId);
		unset($rows[$parentTagId]);

		if ($punishCommonTags)
		{
			$rowsGlobal = self::getGlobalOccurencesForTags(array_keys($rows));

			foreach ($rows as $i => &$row)
			{
				//multiply own occurences by two because we are going to subtract them
				$row['sort'] = $row['post_count'] * 2;
				//subtract global occurencecount
				$row['sort'] -= isset($rowsGlobal[$i]) ? $rowsGlobal[$i]['post_count'] : 0;
			}
		}
		else
		{
			foreach ($rows as $i => &$row)
			{
				$row['sort'] = $row['post_count'];
			}
		}

		usort($rows, function($a, $b)
		{
			return intval($b['sort']) - intval($a['sort']);
		});

		return TagModel::spawnFromDatabaseRows($rows);
	}

	public static function getMostUsedTag()
	{
		$stmt = Sql\Statements::select()
			->setTable('post_tag')
			->addInnerJoin('tag', Sql\Functors::equals('post_tag.tag_id', 'tag.id'))
			->addColumn('tag.*')
			->addColumn(Sql\Functors::alias(Sql\Functors::count('post_tag.post_id'), 'post_count'))
			->setGroupBy('post_tag.tag_id')
			->setOrderBy('post_count', Sql\Statements\SelectStatement::ORDER_DESC)
			->setLimit(1, 0);
		return TagModel::spawnFromDatabaseRow(Core::getDatabase()->fetchOne($stmt));
	}


	private static function getSiblingTagsWithOccurences($parentTagId)
	{
		$stmt = Sql\Statements::select()
			->setTable('tag')
			->addColumn('tag.*')
			->addColumn(Sql\Functors::alias(Sql\Functors::count('post_tag.post_id'), 'post_count'))
			->addInnerJoin('post_tag', Sql\Functors::equals('post_tag.tag_id', 'tag.id'))
			->setGroupBy('tag.id')
			->setOrderBy('post_count', Sql\Statements\SelectStatement::ORDER_DESC)
			->setCriterion(Sql\Functors::exists(Sql\Statements::select()
				->setTable('post_tag pt2')
				->setCriterion(Sql\Functors::conjunction()
					->add(Sql\Functors::equals('pt2.post_id', 'post_tag.post_id'))
					->add(Sql\Functors::equals('pt2.tag_id', new Sql\Binding($parentTagId)))
				)));

		$rows = [];
		foreach (Core::getDatabase()->fetchAll($stmt) as $row)
			$rows[$row['id']] = $row;
		return $rows;
	}

	private static function getGlobalOccurencesForTags($tagIds)
	{
		$stmt = Sql\Statements::select()
			->setTable('tag')
			->addColumn('tag.*')
			->addColumn(Sql\Functors::alias(Sql\Functors::count('post_tag.post_id'), 'post_count'))
			->addInnerJoin('post_tag', Sql\Functors::equals('post_tag.tag_id', 'tag.id'))
			->setCriterion(Sql\InFunctor::fromArray('tag.id', Sql\Binding::fromArray($tagIds)))
			->setGroupBy('tag.id');

		$rows = [];
		foreach (Core::getDatabase()->fetchAll($stmt) as $row)
			$rows[$row['id']] = $row;
		return $rows;
	}
}
