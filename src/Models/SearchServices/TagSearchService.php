<?php
class TagSearchService extends AbstractSearchService
{
	public static function decorateCustom(SqlSelectStatement $stmt)
	{
		$stmt->addColumn(new SqlAliasOperator(new SqlCountOperator('post_tag.post_id'), 'post_count'));
	}
}
