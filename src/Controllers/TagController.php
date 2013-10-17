<?php
class TagController
{
	/**
	* @route /tags
	*/
	public function listAction()
	{
		$this->context->subTitle = 'tags';

		PrivilegesHelper::confirmWithException(Privilege::ListTags);

		$dbQuery = R::$f->begin();
		$dbQuery->select('tag.name, COUNT(1) AS count');
		$dbQuery->from('tag');
		$dbQuery->innerJoin('post_tag');
		$dbQuery->on('tag.id = post_tag.tag_id');
		$dbQuery->groupBy('tag.id');
		$dbQuery->orderBy('count desc, LOWER(tag.name) asc');
		$rows = $dbQuery->get();

		$tags = [];
		$tagDistribution = [];
		foreach ($rows as $row)
		{
			$tags []= strval($row['name']);
			$tagDistribution[$row['name']] = intval($row['count']);
		}

		$this->context->transport->tags = $tags;
		$this->context->transport->tagDistribution = $tagDistribution;
	}
}
