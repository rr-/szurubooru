<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TagSearchService extends AbstractSearchService
{
	public static function decorateCustom(Sql\SelectStatement $stmt)
	{
		$stmt->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'));
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
		$stmt = (new Sql\SelectStatement)
			->setTable('post_tag')
			->addInnerJoin('tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'))
			->addColumn('tag.*')
			->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'))
			->setGroupBy('post_tag.tag_id')
			->setOrderBy('post_count', Sql\SelectStatement::ORDER_DESC)
			->setLimit(1, 0);
		return TagModel::spawnFromDatabaseRow(Database::fetchOne($stmt));
	}


	private static function getSiblingTagsWithOccurences($parentTagId)
	{
		$stmt = (new Sql\SelectStatement)
			->setTable('tag')
			->addColumn('tag.*')
			->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'))
			->addInnerJoin('post_tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'))
			->setGroupBy('tag.id')
			->setOrderBy('post_count', Sql\SelectStatement::ORDER_DESC)
			->setCriterion(new Sql\ExistsFunctor((new Sql\SelectStatement)
				->setTable('post_tag pt2')
				->setCriterion((new Sql\ConjunctionFunctor)
					->add(new Sql\EqualsFunctor('pt2.post_id', 'post_tag.post_id'))
					->add(new Sql\EqualsFunctor('pt2.tag_id', new Sql\Binding($parentTagId)))
				)));

		$rows = [];
		foreach (Database::fetchAll($stmt) as $row)
			$rows[$row['id']] = $row;
		return $rows;
	}

	private static function getGlobalOccurencesForTags($tagIds)
	{
		$stmt = (new Sql\SelectStatement)
			->setTable('tag')
			->addColumn('tag.*')
			->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'))
			->addInnerJoin('post_tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'))
			->setCriterion(Sql\InFunctor::fromArray('tag.id', Sql\Binding::fromArray($tagIds)))
			->setGroupBy('tag.id');

		$rows = [];
		foreach (Database::fetchAll($stmt) as $row)
			$rows[$row['id']] = $row;
		return $rows;
	}
}
