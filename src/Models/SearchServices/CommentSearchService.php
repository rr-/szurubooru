<?php
class CommentSearchService extends AbstractSearchService
{
	public static function decorate(SqlSelectStatement $stmt, $searchQuery)
	{
		$stmt->setTable('comment');
		$stmt->addInnerJoin('post', new SqlEqualsOperator('post_id', 'post.id'));

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$stmt->setCriterion(SqlInOperator::fromArray('post.safety', SqlBinding::fromArray($allowedSafety)));

		$stmt->addOrderBy('comment.id', SqlSelectStatement::ORDER_DESC);
	}
}
