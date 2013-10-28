<?php
class Model_User_QueryBuilder implements AbstractQueryBuilder
{
	public static function build($dbQuery, $query)
	{
		$sortStyle = $query;
		$dbQuery->from('user');

		switch ($sortStyle)
		{
			case 'alpha,asc':
				$dbQuery->orderBy('name')->asc();
				break;
			case 'alpha,desc':
				$dbQuery->orderBy('name')->desc();
				break;
			case 'date,asc':
				$dbQuery->orderBy('join_date')->asc();
				break;
			case 'date,desc':
				$dbQuery->orderBy('join_date')->desc();
				break;
			case 'pending':
				$dbQuery->where('staff_confirmed IS NULL');
				$dbQuery->or('staff_confirmed = 0');
				break;
			default:
				throw new SimpleException('Unknown sort style');
		}
	}
}
