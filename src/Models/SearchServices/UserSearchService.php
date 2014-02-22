<?php
class UserSearchService extends AbstractSearchService
{
	protected static function decorate(SqlSelectStatement $stmt, $searchQuery)
	{
		$stmt->setTable('user');

		$sortStyle = $searchQuery;
		switch ($sortStyle)
		{
			case 'alpha,asc':
				$stmt->setOrderBy(new SqlNoCaseOperator('name'), SqlSelectStatement::ORDER_ASC);
				break;
			case 'alpha,desc':
				$stmt->setOrderBy(new SqlNoCaseOperator('name'), SqlSelectStatement::ORDER_DESC);
				break;
			case 'date,asc':
				$stmt->setOrderBy('join_date', SqlSelectStatement::ORDER_ASC);
				break;
			case 'date,desc':
				$stmt->setOrderBy('join_date', SqlSelectStatement::ORDER_DESC);
				break;
			case 'pending':
				$stmt->setCriterion((new SqlDisjunction)
					->add(new SqlIsNullOperator('staff_confirmed'))
					->add(new SqlEqualsOperator('staff_confirmed', '0')));
				break;
			default:
				throw new SimpleException('Unknown sort style "' . $sortStyle . '"');
		}
	}
}
