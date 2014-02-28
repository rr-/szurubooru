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
		$stmt = new Sql\SelectStatement();
		$stmt->setTable('post_tag');
		$stmt->addColumn('tag_id');
		$stmt->addColumn(new Sql\AliasFunctor(new Sql\CountFunctor('post_tag.post_id'), 'post_count'));
		$stmt->setGroupBy('post_tag.tag_id');
		$stmt->setOrderBy('post_count', Sql\SelectStatement::ORDER_DESC);
		$stmt->setLimit(1, 0);
		return Database::fetchOne($stmt);
	}
}
