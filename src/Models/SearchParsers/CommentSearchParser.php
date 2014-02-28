<?php
use \Chibi\Sql as Sql;

class CommentSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$this->statement->addInnerJoin('post', new Sql\EqualsFunctor('post_id', 'post.id'));

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement->setCriterion(new Sql\ConjunctionFunctor());
		$this->statement->getCriterion()->add(Sql\InFunctor::fromArray('post.safety', Sql\Binding::fromArray($allowedSafety)));
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$this->statement->getCriterion()->add(new Sql\NegationFunctor(new Sql\StringExpression('hidden')));

		$this->statement->addOrderBy('comment.id', Sql\SelectStatement::ORDER_DESC);
	}
}
