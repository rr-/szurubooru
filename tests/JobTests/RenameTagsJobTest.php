<?php
class RenameTagsJobTest extends AbstractTest
{
	public function testRenaming()
	{
		$this->grantAccess('renameTags');

		list ($sourceTag, $randomTag, $targetTag)
			= $this->tagMocker->mockMultiple(3);

		$posts = $this->postMocker->mockMultiple(3);
		$posts[0]->setTags([$sourceTag]);
		$posts[1]->setTags([$randomTag]);
		$posts[2]->setTags([$sourceTag, $randomTag]);
		PostModel::save($posts);

		$this->assert->doesNotThrow(function() use ($sourceTag, $targetTag)
		{
			Api::run(
				new RenameTagsJob(),
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
	}

	public function testRenamingToExistingTag()
	{
		$this->grantAccess('renameTags');

		list ($sourceTag, $targetTag)
			= $this->tagMocker->mockMultiple(2);

		$post = $this->postMocker->mockSingle();
		$post->setTags([$sourceTag, $targetTag]);
		PostModel::save($post);

		$this->assert->throws(function() use ($sourceTag, $targetTag)
		{
			Api::run(
				new RenameTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => $sourceTag->getName(),
					JobArgs::ARG_TARGET_TAG_NAME => $targetTag->getName(),
				]);
		}, 'Target tag already exists');
	}

	public function testRenamingToItself()
	{
		$this->grantAccess('renameTags');

		$sourceTag = $this->tagMocker->mockSingle();

		$post = $this->postMocker->mockSingle();
		$post->setTags([$sourceTag]);
		PostModel::save($post);

		$this->assert->throws(function() use ($sourceTag)
		{
			Api::run(
				new RenameTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => $sourceTag->getName(),
					JobArgs::ARG_TARGET_TAG_NAME => $sourceTag->getName(),
				]);
		}, 'Source and target tag are the same');
	}
}
