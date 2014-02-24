<?php
class UserSearchParser extends AbstractSearchParser
{
	protected function processSimpleToken($value, $neg)
	{
		if ($neg)
			return false;

		if ($value == 'pending')
		{
			$this->statement->setCriterion((new SqlDisjunctionFunctor)
				->add(new SqlIsFunctor('staff_confirmed', new SqlNullFunctor()))
				->add(new SqlEqualsFunctor('staff_confirmed', '0')));
			return true;
		}
		return false;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'alpha')
			$this->statement->setOrderBy(new SqlNoCaseFunctor('name'), $orderDir);
		elseif ($orderByString == 'date')
			$this->statement->setOrderBy('join_date', $orderDir);
		else
			return false;

		return true;
	}
}
