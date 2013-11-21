<?php
class model_Tag_QueryBuilder implements AbstractQueryBuilder
{
	public static function build($dbQuery, $query)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$limitQuery = false;
		$dbQuery
			->addSql(', COUNT(post_tag.post_id)')
			->as('post_count')
			->from('tag')
			->innerJoin('post_tag')
			->on('tag.id = post_tag.tag_id')
			->innerJoin('post')
			->on('post.id = post_tag.post_id')
			->where('safety IN (' . R::genSlots($allowedSafety) . ')');
		foreach ($allowedSafety as $s)
			$dbQuery->put($s);

		$orderToken = null;

		if ($query !== null)
		{
			$tokens = preg_split('/\s+/', $query);
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
					$limitQuery = true;
					if (strlen($token) >= 3)
						$token = '%' . $token;
					$token .= '%';
					$dbQuery
						->and('LOWER(tag.name)')
						->like('LOWER(?)')
						->put($token);
				}
			}
		}

		$dbQuery->groupBy('tag.id');
		if ($orderToken)
			self::order($dbQuery,$orderToken);


		if ($limitQuery)
			$dbQuery->limit(15);
	}

	private static function order($dbQuery, $value)
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
				$dbQuery->orderBy('post_count');
				break;

			case 'alpha':
				$dbQuery->orderBy('name');
				break;
		}

		if ($orderDir == 'asc')
			$dbQuery->asc();
		else
			$dbQuery->desc();
	}
}
