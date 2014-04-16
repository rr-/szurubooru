<?php
use \Chibi\Sql as Sql;

class TagSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement
			->addInnerJoin('post_tag', new Sql\EqualsFunctor('tag.id', 'post_tag.tag_id'))
			->addInnerJoin('post', new Sql\EqualsFunctor('post.id', 'post_tag.post_id'))
			->setCriterion((new Sql\ConjunctionFunctor)->add(Sql\InFunctor::fromArray('safety', Sql\Binding::fromArray($allowedSafety))))
			->setGroupBy('tag.id');
	}

	protected function processSimpleToken($value, $neg)
	{
		if ($neg)
			return false;

		if (strlen($value) >= 3)
			$value = '%' . $value;
		$value .= '%';

		$this->statement->getCriterion()->add(new Sql\NoCaseFunctor(new Sql\LikeFunctor('tag.name', new Sql\Binding($value))));
		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'popularity')
			$this->statement->setOrderBy('post_count', $orderDir);
		elseif ($orderByString == 'alpha')
			$this->statement->setOrderBy(new Sql\CaseFunctor('tag.name'), $orderDir);
		else
			return false;
		return true;
	}
}
