<?php
class UserSearchService extends AbstractSearchService
{
	protected static function decorate(SQLQuery $sqlQuery, $searchQuery)
	{
		$sqlQuery->from('user');

		$sortStyle = $searchQuery;
		switch ($sortStyle)
		{
			case 'alpha,asc':
				$sqlQuery->orderBy('name')->collate()->nocase()->asc();
				break;
			case 'alpha,desc':
				$sqlQuery->orderBy('name')->collate()->nocase()->desc();
				break;
			case 'date,asc':
				$sqlQuery->orderBy('join_date')->asc();
				break;
			case 'date,desc':
				$sqlQuery->orderBy('join_date')->desc();
				break;
			case 'pending':
				$sqlQuery->where('staff_confirmed IS NULL');
				$sqlQuery->or('staff_confirmed = 0');
				break;
			default:
				throw new SimpleException('Unknown sort style "' . $sortStyle . '"');
		}
	}
}
