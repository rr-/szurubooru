<?php
class CommentSearchService extends AbstractSearchService
{
	public static function decorate(SqlQuery $sqlQuery, $searchQuery)
	{
		$sqlQuery
			->from('comment')
			->where('post_id')
			->is()->not('NULL')
			->orderBy('id')
			->desc();
	}
}
