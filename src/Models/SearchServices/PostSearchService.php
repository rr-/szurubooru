<?php
class PostSearchService extends AbstractSearchService
{
	public static function getPostIdsAround($searchQuery, $postId)
	{
		return Database::transaction(function() use ($searchQuery, $postId)
		{
			$stmt = new SqlRawStatement('CREATE TEMPORARY TABLE IF NOT EXISTS post_search(id INTEGER PRIMARY KEY, post_id INTEGER)');
			Database::exec($stmt);

			$stmt = new SqlDeleteStatement();
			$stmt->setTable('post_search');
			Database::exec($stmt);

			$innerStmt = new SqlSelectStatement($searchQuery);
			$innerStmt->setColumn('id');
			$innerStmt->setTable('post');
			self::decorateParser($innerStmt, $searchQuery);
			$stmt = new SqlInsertStatement();
			$stmt->setTable('post_search');
			$stmt->setSource(['post_id'], $innerStmt);
			Database::exec($stmt);

			$stmt = new SqlSelectStatement();
			$stmt->setTable('post_search');
			$stmt->setColumn('id');
			$stmt->setCriterion(new SqlEqualsOperator('post_id', new SqlBinding($postId)));
			$rowId = Database::fetchOne($stmt)['id'];

			//it's possible that given post won't show in search results:
			//it can be hidden, it can have prohibited safety etc.
			if (!$rowId)
				return [null, null];

			$rowId = intval($rowId);
			$stmt->setColumn('post_id');

			$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($rowId - 1)));
			$nextPostId = Database::fetchOne($stmt)['post_id'];

			$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($rowId + 1)));
			$prevPostId = Database::fetchOne($stmt)['post_id'];

			return [$prevPostId, $nextPostId];
		});
	}
}
