<?php
class TagModelTest extends AbstractTest
{
	public function testSavingAndRetrieving()
	{
		$tag = TagModel::spawn();
		$tag->setName('test');

		TagModel::save($tag);
		$otherTag = TagModel::getById($tag->getId());

		$this->assert->areEqual($tag->getName(), $otherTag->getName());
		$this->assert->areEqual(time(), $otherTag->getCreationDate());
		$this->assert->areEqual(time(), $otherTag->getUpdateDate());
	}
}
