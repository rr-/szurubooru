<?php
class TagMocker extends AbstractMocker implements IMocker
{
	public function mockSingle()
	{
		$tag = TagModel::spawn();
		$tag->setName(uniqid());
		return TagModel::save($tag);
	}
}
