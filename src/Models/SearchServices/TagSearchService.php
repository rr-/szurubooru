<?php
class TagSearchService extends AbstractSearchService
{
	public static function decorateCustom(SqlSelectStatement $stmt)
	{
		$stmt->addColumn(new SqlAliasFunctor(new SqlCountFunctor('post_tag.post_id'), 'post_count'));
	}

	public static function getMostUsedTag()
	{
		$stmt = new SqlSelectStatement();
		$stmt->setTable('post_tag');
		$stmt->addColumn('tag_id');
		$stmt->addColumn(new SqlAliasFunctor(new SqlCountFunctor('post_tag.post_id'), 'post_count'));
		$stmt->setGroupBy('post_tag.tag_id');
		$stmt->setOrderBy('post_count', SqlSelectStatement::ORDER_DESC);
		$stmt->setLimit(1, 0);
		return Database::fetchOne($stmt);
	}
}
