<?php
class TagSearchService extends AbstractSearchService
{
	public static function decorate(SqlSelectStatement $stmt, $searchQuery)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$stmt
			->addColumn('COUNT(post_tag.post_id) AS post_count')
			->setTable('tag')
			->addInnerJoin('post_tag', new SqlEqualsOperator('tag.id', 'post_tag.tag_id'))
			->addInnerJoin('post', new SqlEqualsOperator('post.id', 'post_tag.post_id'));
		$stmt->setCriterion((new SqlConjunction)->add(SqlInOperator::fromArray('safety', SqlBinding::fromArray($allowedSafety))));

		$orderToken = null;

		if ($searchQuery !== null)
		{
			$tokens = preg_split('/\s+/', $searchQuery);
			foreach ($tokens as $token)
			{
				if (strpos($token, ':') !== false)
				{
					list ($key, $value) = explode(':', $token);

					if ($key == 'order')
						$orderToken = $value;
					else
						throw new SimpleException('Unknown key: ' . $key);
				}
				else
				{
					if (strlen($token) >= 3)
						$token = '%' . $token;
					$token .= '%';
					$stmt->getCriterion()->add(new SqlNoCaseOperator(new SqlLikeOperator('tag.name', new SqlBinding($token))));
				}
			}
		}

		$stmt->groupBy('tag.id');
		if ($orderToken)
			self::order($stmt,$orderToken);
	}

	private static function order(SqlSelectStatement $stmt, $value)
	{
		if (strpos($value, ',') !== false)
		{
			list ($orderColumn, $orderDir) = explode(',', $value);
		}
		else
		{
			$orderColumn = $value;
			$orderDir = 'asc';
		}

		switch ($orderColumn)
		{
			case 'popularity':
				$stmt->setOrderBy('post_count',
					$orderDir == 'asc'
						? SqlSelectStatement::ORDER_ASC
						: SqlSelectStatement::ORDER_DESC);
				break;

			case 'alpha':
				$stmt->setOrderBy(new SqlNoCaseOperator('tag.name'),
					$orderDir == 'asc'
						? SqlSelectStatement::ORDER_ASC
						: SqlSelectStatement::ORDER_DESC);
				break;
		}
	}
}
