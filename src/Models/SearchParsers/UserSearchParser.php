<?php
use \Chibi\Sql as Sql;

class UserSearchParser extends AbstractSearchParser
{
	protected function processSimpleToken($value, $neg)
	{
		if ($neg)
			return false;

		if ($value == 'pending')
		{
			$this->statement->setCriterion((new Sql\DisjunctionFunctor)
				->add(new Sql\IsFunctor('staff_confirmed', new Sql\NullFunctor()))
				->add(new Sql\EqualsFunctor('staff_confirmed', '0')));
			return true;
		}
		return false;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'alpha')
			$this->statement->setOrderBy(new Sql\NoCaseFunctor('name'), $orderDir);
		elseif ($orderByString == 'date')
			$this->statement->setOrderBy('join_date', $orderDir);
		else
			return false;

		return true;
	}
}
