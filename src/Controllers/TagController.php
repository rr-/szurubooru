<?php
class TagController
{
	/**
	* @route /tags
	*/
	public function listAction()
	{
		$this->context->subTitle = 'tags';

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ListTags);

		$dbQuery = R::$f->begin();
		$dbQuery->select('tag.name, COUNT(1) AS count');
		$dbQuery->from('tag');
		$dbQuery->innerJoin('post_tag');
		$dbQuery->on('tag.id = post_tag.tag_id');
		$dbQuery->groupBy('tag.id');
		$rows = $dbQuery->get();

		$tags = [];
		$tagDistribution = [];
		foreach ($rows as $row)
		{
			$tags []= $row['name'];
			$tagDistribution[$row['name']] = $row['count'];
		}
		array_multisort(
			array_values($tagDistribution), SORT_DESC, SORT_NUMERIC,
			array_keys($tagDistribution), SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE,
			$tagDistribution);

		$this->context->transport->tags = $tags;
		$this->context->transport->tagDistribution = $tagDistribution;
	}
}
