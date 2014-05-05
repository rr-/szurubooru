<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TagEntity extends AbstractEntity implements IValidatable
{
	protected $name;

	public function validate()
	{
		//todo
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getPostCount()
	{
		if ($this->hasCache('post_count'))
			return $this->getCache('post_count');

		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('post_tag');
		$stmt->setCriterion(new Sql\EqualsFunctor('tag_id', new Sql\Binding($this->id)));
		return Database::fetchOne($stmt)['count'];
	}
}
