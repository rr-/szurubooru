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
			$this->statement->setCriterion(Sql\Functors::disjunction()
				->add(Sql\Functors::is('staff_confirmed', Sql\Functors::null()))
				->add(Sql\Functors::equals('staff_confirmed', '0')));
			return true;
		}
		return false;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'alpha')
			$this->statement->setOrderBy(Sql\Functors::noCase('name'), $orderDir);
		elseif ($orderByString == 'date')
			$this->statement->setOrderBy('join_date', $orderDir);
		else
			return false;

		return true;
	}
}
