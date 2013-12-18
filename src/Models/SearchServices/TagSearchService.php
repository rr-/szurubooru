<?php
class TagSearchService extends AbstractSearchService
{
	public static function decorate(SqlQuery $sqlQuery, $searchQuery)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$limitQuery = false;
		$sqlQuery
			->raw(', COUNT(post_tag.post_id)')
			->as('post_count')
			->from('tag')
			->innerJoin('post_tag')
			->on('tag.id = post_tag.tag_id')
			->innerJoin('post')
			->on('post.id = post_tag.post_id')
			->where('safety')->in()->genSlots($allowedSafety);
		foreach ($allowedSafety as $s)
			$sqlQuery->put($s);

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
					$limitQuery = true;
					if (strlen($token) >= 3)
						$token = '%' . $token;
					$token .= '%';
					$sqlQuery
						->and('LOWER(tag.name)')
						->like('LOWER(?)')
						->put($token);
				}
			}
		}

		$sqlQuery->groupBy('tag.id');
		if ($orderToken)
			self::order($sqlQuery,$orderToken);


		if ($limitQuery)
			$sqlQuery->limit(15);
	}

	private static function order(SqlQuery $sqlQuery, $value)
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
				$sqlQuery->orderBy('post_count');
				break;

			case 'alpha':
				$sqlQuery->orderBy('name');
				break;
		}

		if ($orderDir == 'asc')
			$sqlQuery->asc();
		else
			$sqlQuery->desc();
	}
}
