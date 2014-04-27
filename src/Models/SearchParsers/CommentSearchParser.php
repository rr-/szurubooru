<?php
use \Chibi\Sql as Sql;

class CommentSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$this->statement->addInnerJoin('post', new Sql\EqualsFunctor('post_id', 'post.id'));
		$crit = new Sql\ConjunctionFunctor();

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$crit->add(Sql\InFunctor::fromArray('post.safety', Sql\Binding::fromArray($allowedSafety)));

		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$crit->add(new Sql\NegationFunctor(new Sql\StringExpression('hidden')));

		$this->statement->setCriterion($crit);
		$this->statement->addOrderBy('comment.id', Sql\SelectStatement::ORDER_DESC);
	}
}
