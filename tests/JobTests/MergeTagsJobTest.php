<?php
class MergeTagsJobTest extends AbstractTest
{
	public function testMerging()
	{
		$this->grantAccess('mergeTags');

		list ($sourceTag, $randomTag, $targetTag)
			= $this->tagMocker->mockMultiple(3);

		$posts = $this->postMocker->mockMultiple(6);
		$posts[0]->setTags([$sourceTag]);
		$posts[1]->setTags([$randomTag]);
		$posts[2]->setTags([$sourceTag, $randomTag]);
		$posts[3]->setTags([$sourceTag, $targetTag]);
		$posts[4]->setTags([$randomTag, $targetTag]);
		$posts[5]->setTags([$sourceTag, $randomTag, $targetTag]);
		PostModel::save($posts);

		$this->assert->doesNotThrow(function() use ($sourceTag, $targetTag)
		{
			Api::run(
				new MergeTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => $sourceTag->getName(),
					JobArgs::ARG_TARGET_TAG_NAME => $targetTag->getName(),
				]);
		});

		foreach ($posts as $k => $post)
			$posts[$k] = PostModel::getById($post->getId());

		$this->testSupport->assertTagNames($posts[0], [$targetTag]);
		$this->testSupport->assertTagNames($posts[1], [$randomTag]);
		$this->testSupport->assertTagNames($posts[2], [$randomTag, $targetTag]);
		$this->testSupport->assertTagNames($posts[3], [$targetTag]);
		$this->testSupport->assertTagNames($posts[4], [$randomTag, $targetTag]);
		$this->testSupport->assertTagNames($posts[5], [$randomTag, $targetTag]);
	}

	public function testMergingToNonExistingTag()
	{
		$this->grantAccess('mergeTags');

		$sourceTag = $this->tagMocker->mockSingle();

		$post = $this->postMocker->mockSingle();
		$post->setTags([$sourceTag]);
		PostModel::save($post);

		$this->assert->throws(function() use ($sourceTag)
		{
			Api::run(
				new MergeTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => $sourceTag->getName(),
					JobArgs::ARG_TARGET_TAG_NAME => 'nonsense',
				]);
		}, 'Invalid tag name');
	}

	public function testMergingToItself()
	{
		$this->grantAccess('mergeTags');

		$sourceTag = $this->tagMocker->mockSingle();

		$post = $this->postMocker->mockSingle();
		$post->setTags([$sourceTag]);
		PostModel::save($post);

		$this->assert->throws(function() use ($sourceTag)
		{
			Api::run(
				new MergeTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => $sourceTag->getName(),
					JobArgs::ARG_TARGET_TAG_NAME => $sourceTag->getName(),
				]);
		}, 'Source and target tag are the same');
	}
}
