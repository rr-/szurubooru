<?php
class CommentSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$this->statement->addInnerJoin('post', new SqlEqualsOperator('post_id', 'post.id'));

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement->setCriterion(new SqlConjunction());
		$this->statement->getCriterion()->add(SqlInOperator::fromArray('post.safety', SqlBinding::fromArray($allowedSafety)));
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$this->statement->getCriterion()->add(new SqlNegationOperator(new SqlStringExpression('hidden')));

		$this->statement->addOrderBy('comment.id', SqlSelectStatement::ORDER_DESC);
	}
}
