<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TagSearchService extends AbstractSearchService
{
	public static function decorateCustom(Sql\SelectStatement $stmt)
	{
		$stmt->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'));
	}

	public static function getRelatedTagRows($parentTagName, $limit)
	{
		$parentTagEntity = TagModel::findByName($parentTagName, false);
		if (empty($parentTagEntity))
			return [];
		$parentTagId = $parentTagEntity->id;

		//get tags that appear with selected tag along with their occurence frequency
		$stmt = (new Sql\SelectStatement)
			->setTable('tag')
			->addColumn('tag.*')
			->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'))
			->addInnerJoin('post_tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'))
			->setGroupBy('tag.id')
			->setOrderBy('post_count', Sql\SelectStatement::ORDER_DESC)
			->setLimit($limit + 1, 0)
			->setCriterion(new Sql\ExistsFunctor((new Sql\SelectStatement)
				->setTable('post_tag pt2')
				->setCriterion((new Sql\ConjunctionFunctor)
					->add(new Sql\EqualsFunctor('pt2.post_id', 'post_tag.post_id'))
					->add(new Sql\EqualsFunctor('pt2.tag_id', new Sql\Binding($parentTagId)))
				)));

		$rows1 = [];
		foreach (Database::fetchAll($stmt) as $row)
			$rows1[$row['id']] = $row;

		//get the same tags, but this time - get global occurence frequency
		$stmt = (new Sql\SelectStatement)
			->setTable('tag')
			->addColumn('tag.*')
			->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'))
			->addInnerJoin('post_tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'))
			->setCriterion(Sql\InFunctor::fromArray('tag.id', Sql\Binding::fromArray(array_keys($rows1))))
			->setGroupBy('tag.id');

		$rows2 = [];
		foreach (Database::fetchAll($stmt) as $row)
			$rows2[$row['id']] = $row;

		$rows = [];
		foreach ($rows1 as $i => $row)
		{
			//multiply own occurences by two because we are going to subtract them
			$row['sort'] = $row['post_count'] * 2;
			//subtract global occurencecount
			$row['sort'] -= isset($rows2[$i]) ? $rows2[$i]['post_count'] : 0;

			if ($row['id'] != $parentTagId)
				$rows []= $row;
		}

		usort($rows, function($a, $b) { return intval($b['sort']) - intval($a['sort']); });

		return $rows;
	}

	public static function getMostUsedTag()
	{
		$stmt = (new Sql\SelectStatement)
			->setTable('post_tag')
			->addColumn('tag_id')
			->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'))
			->setGroupBy('post_tag.tag_id')
			->setOrderBy('post_count', Sql\SelectStatement::ORDER_DESC)
			->setLimit(1, 0);
		return Database::fetchOne($stmt);
	}
}
