<?php
use \Chibi\Sql as Sql;

final class TagEntity extends AbstractEntity implements IValidatable, ISerializable
{
	private $name;
	private $creationDate;
	private $updateDate;

	public function fillNew()
	{
	}

	public function fillFromDatabase($row)
	{
		$this->id = (int) $row['id'];
		$this->name = $row['name'];
		$this->creationDate = TextHelper::toIntegerOrNull($row['creation_date']);
		$this->updateDate = TextHelper::toIntegerOrNull($row['update_date']);

		if (isset($row['post_count']))
			$this->setCache('post_count', (int) $row['post_count']);
	}

	public function serializeToArray()
	{
		return
		[
			'name' => $this->getName(),
			'post-count' => $this->getPostCount(),
		];
	}

	public function validate()
	{
		$minLength = Core::getConfig()->tags->minLength;
		$maxLength = Core::getConfig()->tags->maxLength;
		$regex = Core::getConfig()->tags->regex;

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
		$this->name = $name === null ? null : trim($name);
	}

	public function getName()
	{
		return $this->name;
	}

	public function getCreationDate()
	{
		return $this->creationDate;
	}

	public function getUpdateDate()
	{
		return $this->updateDate;
	}

	public function getPostCount()
	{
		if ($this->hasCache('post_count'))
			return $this->getCache('post_count');

		$stmt = \Chibi\Sql\Statements::select();
		$stmt->setColumn(Sql\Functors::alias(Sql\Functors::count('1'), 'post_count'));
		$stmt->setTable('post_tag');
		$stmt->setCriterion(Sql\Functors::equals('tag_id', new Sql\Binding($this->getId())));
		$row = Core::getDatabase()->fetchOne($stmt);
		$this->setCache('post_count', (int) $row['post_count']);
		return $this->getCache('post_count');
	}
}
