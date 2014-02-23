<?php
class UserSearchParser extends AbstractSearchParser
{
	protected function processSimpleToken($value, $neg)
	{
		if ($neg)
			return false;

		if ($value == 'pending')
		{
			$this->statement->setCriterion((new SqlDisjunction)
				->add(new SqlIsNullOperator('staff_confirmed'))
				->add(new SqlEqualsOperator('staff_confirmed', '0')));
			return true;
		}
		return false;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'alpha')
			$this->statement->setOrderBy(new SqlNoCaseOperator('name'), $orderDir);
		elseif ($orderByString == 'date')
			$this->statement->setOrderBy('join_date', $orderDir);
		else
			return false;

		return true;
	}
}
