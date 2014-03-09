<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TagSearchService extends AbstractSearchService
{
	public static function decorateCustom(Sql\SelectStatement $stmt)
	{
		$stmt->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'));
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
