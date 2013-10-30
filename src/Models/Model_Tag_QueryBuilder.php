<?php
class model_Tag_QueryBuilder implements AbstractQueryBuilder
{
	public static function build($dbQuery, $query)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$limitQuery = false;
		$dbQuery
			->addSql(', COUNT(post_tag.post_id)')
			->as('count')
			->from('tag')
			->innerJoin('post_tag')
			->on('tag.id = post_tag.tag_id')
			->innerJoin('post')
			->on('post.id = post_tag.post_id')
			->where('safety IN (' . R::genSlots($allowedSafety) . ')');
		foreach ($allowedSafety as $s)
			$dbQuery->put($s);
		if ($query !== null)
		{
			$limitQuery = true;
			if (strlen($query) >= 3)
				$query = '%' . $query;
			$query .= '%';
			$dbQuery
				->and('LOWER(tag.name)')
				->like('LOWER(?)')
				->put($query);
		}
		$dbQuery
			->groupBy('tag.id')
			->orderBy('LOWER(tag.name)')
			->asc();
		if ($limitQuery)
			$dbQuery->limit(15);
	}
}
