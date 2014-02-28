<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TagEntity extends AbstractEntity
{
	public $name;

	public function getPostCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('post_tag');
		$stmt->setCriterion(new Sql\EqualsFunctor('tag_id', new Sql\Binding($this->id)));
		return Database::fetchOne($stmt)['count'];
	}
}
