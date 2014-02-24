<?php
class CommentSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$this->statement->addInnerJoin('post', new SqlEqualsFunctor('post_id', 'post.id'));

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement->setCriterion(new SqlConjunctionFunctor());
		$this->statement->getCriterion()->add(SqlInFunctor::fromArray('post.safety', SqlBinding::fromArray($allowedSafety)));
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$this->statement->getCriterion()->add(new SqlNegationFunctor(new SqlStringExpression('hidden')));

		$this->statement->addOrderBy('comment.id', SqlSelectStatement::ORDER_DESC);
	}
}
