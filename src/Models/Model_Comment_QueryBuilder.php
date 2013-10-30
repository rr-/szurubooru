<?php
class Model_Comment_QueryBuilder implements AbstractQueryBuilder
{
	public static function build($dbQuery, $query)
	{
		$dbQuery
			->from('comment')
			->where('post_id')
			->is()->not('NULL')
			->orderBy('id')
			->desc();
	}
}
