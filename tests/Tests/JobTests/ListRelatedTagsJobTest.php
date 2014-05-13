<?php
class ListRelatedTagsJobTest extends AbstractTest
{
	public function testPaging()
	{
		$this->grantAccess('listTags');
		$this->grantAccess('listPosts');

		$tags = $this->tagMocker->mockMultiple(3);
		getConfig()->browsing->tagsRelated = 1;

		$post = $this->postMocker->mockSingle();
		$post->setTags($tags);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function() use ($tags)
		{
			return Api::run(new ListRelatedTagsJob(), [
				JobArgs::ARG_TAG_NAME => $tags[0]->getName()]);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);

		$ret = $this->assert->doesNotThrow(function() use ($tags)
		{
			return Api::run(new ListRelatedTagsJob(), [
				JobArgs::ARG_TAG_NAME => $tags[0]->getName(),
				JobArgs::ARG_PAGE_NUMBER => 2]);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(2, $ret->page);
	}

	public function testSimpleRelations()
	{
		$this->grantAccess('listTags');
		$this->grantAccess('listPosts');

		$tags = $this->tagMocker->mockMultiple(3);
		$post = $this->postMocker->mockSingle();
		$post->setTags($tags);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function() use ($tags)
		{
			return Api::run(new ListRelatedTagsJob(), [
				JobArgs::ARG_TAG_NAME => $tags[0]->getName()]);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual(2, count($ret->entities));
		$this->assert->areEqual(1, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);
		$this->assert->areEqual($tags[1]->getName(), $ret->entities[1]->getName());
		$this->assert->areEqual($tags[2]->getName(), $ret->entities[0]->getName());
	}
}
