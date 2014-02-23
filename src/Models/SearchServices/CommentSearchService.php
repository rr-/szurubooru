<?php
class CommentSearchService extends AbstractSearchService
{
	public static function decorate(SqlSelectStatement $stmt, $searchQuery)
	{
		$stmt->setTable('comment');
		$stmt->addInnerJoin('post', new SqlEqualsOperator('post_id', 'post.id'));

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$stmt->setCriterion(new SqlConjunction());
		$stmt->getCriterion()->add(SqlInOperator::fromArray('post.safety', SqlBinding::fromArray($allowedSafety)));
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$stmt->getCriterion()->add(new SqlNegationOperator(new SqlStringExpression('hidden')));

		$stmt->addOrderBy('comment.id', SqlSelectStatement::ORDER_DESC);
	}
}
