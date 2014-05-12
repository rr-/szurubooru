<?php
class RenameTagsJobTest extends AbstractTest
{
	public function testRenaming()
	{
		$this->grantAccess('renameTags');

		$sourceTag = $this->mockTag();
		$randomTag = $this->mockTag();
		$targetTag = $this->mockTag();

		$posts = [];
		foreach (range(0, 2) as $i)
			$posts []= $this->mockPost($this->mockUser());

		$posts[0]->setTags([$sourceTag]);
		$posts[1]->setTags([$randomTag]);
		$posts[2]->setTags([$sourceTag, $randomTag]);

		foreach ($posts as $post)
			PostModel::save($post);

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

		$this->assertTagNames($posts[0], [$targetTag]);
		$this->assertTagNames($posts[1], [$randomTag]);
		$this->assertTagNames($posts[2], [$randomTag, $targetTag]);
	}

	public function testRenamingToExistingTag()
	{
		$this->grantAccess('renameTags');

		$sourceTag = $this->mockTag();
		$post = $this->mockPost($this->mockUser());
		$post->setTags([$sourceTag]);
		PostModel::save($post);

		$targetTag = $this->mockTag();
		$post = $this->mockPost($this->mockUser());
		$post->setTags([$targetTag]);
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

		$sourceTag = $this->mockTag();
		$post = $this->mockPost($this->mockUser());
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

	private function assertTagNames($post, $tags)
	{
		$tagNames = $this->getTagNames($tags);
		$postTagNames = $this->getTagNames($post->getTags());
		$this->assert->areEquivalent($tagNames, $postTagNames);
	}

	private function getTagNames($tags)
	{
		$tagNames = array_map(
			function($tag)
			{
				return $tag->getName();
			}, $tags);
		natcasesort($tagNames);
		$tagNames = array_values($tagNames);
		return $tagNames;
	}
}
