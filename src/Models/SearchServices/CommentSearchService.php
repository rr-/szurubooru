<?php
class CommentSearchService extends AbstractSearchService
{
	public static function decorate(SqlQuery $sqlQuery, $searchQuery)
	{
		$sqlQuery
			->from('comment')
			->innerJoin('post')
			->on('post_id = post.id');

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		if (empty($allowedSafety))
			$sqlQuery->where('0');
		else
			$sqlQuery->where('post.safety')->in()->genSlots($allowedSafety)->put($allowedSafety);

		$sqlQuery
			->orderBy('comment.id')
			->desc();
	}
}
