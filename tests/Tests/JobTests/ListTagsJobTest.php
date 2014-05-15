<?php
class ListTagsJobTest extends AbstractTest
{
	public function testPaging()
	{
		$this->grantAccess('listTags');
		$this->grantAccess('listPosts');

		$tags = $this->tagMocker->mockMultiple(3);
		Core::getConfig()->browsing->tagsPerPage = 2;

		$post = $this->postMocker->mockSingle();
		$post->setTags($tags);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob(), []);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(2, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob(), [JobArgs::ARG_PAGE_NUMBER => 2]);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(2, $ret->page);
	}

	public function testOrderPopularity()
	{
		$this->grantAccess('listTags');
		$this->grantAccess('listPosts');
		$tags = $this->tagMocker->mockMultiple(2);

		$posts = $this->postMocker->mockMultiple(2);
		$posts[0]->setTags([$tags[0]]);
		$posts[1]->setTags($tags);
		PostModel::save($posts);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob, [JobArgs::ARG_QUERY => 'order:popularity']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($tags[0]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($tags[1]->getName(), $ret->entities[1]->getName());

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob, [JobArgs::ARG_QUERY => '-order:popularity']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($tags[1]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($tags[0]->getName(), $ret->entities[1]->getName());
	}

	public function testOrderAlphanumeric()
	{
		$this->grantAccess('listTags');
		$this->grantAccess('listPosts');

		$tags = $this->tagMocker->mockMultiple(2);
		$tags[0]->setName('aaa');
		$tags[1]->setName('bbb');
		TagModel::save($tags);

		$post = $this->postMocker->mockSingle();
		$post->setTags($tags);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob, [JobArgs::ARG_QUERY => 'order:alpha']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($tags[1]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($tags[0]->getName(), $ret->entities[1]->getName());

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob, [JobArgs::ARG_QUERY => '-order:alpha']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($tags[0]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($tags[1]->getName(), $ret->entities[1]->getName());
	}

	public function testFilter()
	{
		$this->grantAccess('listTags');
		$this->grantAccess('listPosts');

		$tags = $this->tagMocker->mockMultiple(2);
		$tags[0]->setName('alice');
		$tags[1]->setName('bob');
		TagModel::save($tags);

		$post = $this->postMocker->mockSingle();
		$post->setTags($tags);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListTagsJob, [JobArgs::ARG_QUERY => 'LiC']);
		});

		$this->assert->areEqual(1, $ret->entityCount);
		$this->assert->areEqual($tags[0]->getName(), $ret->entities[0]->getName());
	}
}
