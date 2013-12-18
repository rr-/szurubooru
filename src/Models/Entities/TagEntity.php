<?php
class TagEntity extends AbstractEntity
{
	public $name;

	public function getPostCount()
	{
		$query = (new SqlQuery)
			->select('count(*)')->as('count')
			->from('post_tag')
			->where('tag_id = ?')->put($this->id);
		return Database::fetchOne($query)['count'];
	}
}
