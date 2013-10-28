<?php
class Model_Comment_QueryBuilder implements AbstractQueryBuilder
{
	public static function build($dbQuery, $query)
	{
		$dbQuery->from('comment');
		$dbQuery->orderBy('id')->desc();
	}
}
