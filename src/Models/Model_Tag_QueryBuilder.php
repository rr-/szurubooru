<?php
class model_Tag_QueryBuilder implements AbstractQueryBuilder
{
	public static function build($dbQuery, $query)
	{
		$limitQuery = false;
		$dbQuery->addSql(', COUNT(post_tag.post_id)')->as('count');
		$dbQuery->from('tag');
		$dbQuery->innerJoin('post_tag');
		$dbQuery->on('tag.id = post_tag.tag_id');
		if ($query !== null)
		{
			$limitQuery = true;
			if (strlen($query) >= 3)
				$query = '%' . $query;
			$query .= '%';
			$dbQuery->where('LOWER(tag.name) LIKE LOWER(?)')->put($query);
		}
		$dbQuery->groupBy('tag.id');
		$dbQuery->orderBy('LOWER(tag.name)')->asc();
		if ($limitQuery)
			$dbQuery->limit(15);
	}
}
