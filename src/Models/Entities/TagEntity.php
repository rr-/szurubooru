<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TagEntity extends AbstractEntity implements IValidatable
{
	protected $name;

	public function validate()
	{
		$minLength = getConfig()->tags->minLength;
		$maxLength = getConfig()->tags->maxLength;
		$regex = getConfig()->tags->regex;

		$name = $this->getName();

		if (strlen($name) < $minLength)
			throw new SimpleException('Tag must have at least %d characters', $minLength);
		if (strlen($name) > $maxLength)
			throw new SimpleException('Tag must have at most %d characters', $maxLength);

		if (!preg_match($regex, $name))
			throw new SimpleException('Invalid tag "%s"', $name);

		if (preg_match('/^\.\.?$/', $name))
			throw new SimpleException('Invalid tag "%s"', $name);
	}

	public function setName($name)
	{
		$this->name = trim($name);
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
		$stmt->setCriterion(new Sql\EqualsFunctor('tag_id', new Sql\Binding($this->getId())));
		return Database::fetchOne($stmt)['count'];
	}
}
