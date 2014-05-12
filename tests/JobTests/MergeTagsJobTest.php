<?php
class MergeTagsJobTest extends AbstractTest
{
	public function testMerging()
	{
		$this->grantAccess('mergeTags');

		$sourceTag = $this->mockTag();
		$randomTag = $this->mockTag();
		$targetTag = $this->mockTag();

		$posts = [];
		foreach (range(0, 5) as $i)
			$posts []= $this->mockPost($this->mockUser());

		$posts[0]->setTags([$sourceTag]);
		$posts[1]->setTags([$randomTag]);
		$posts[2]->setTags([$sourceTag, $randomTag]);

		$posts[3]->setTags([$sourceTag, $targetTag]);
		$posts[4]->setTags([$randomTag, $targetTag]);
		$posts[5]->setTags([$sourceTag, $randomTag, $targetTag]);

		foreach ($posts as $post)
			PostModel::save($post);

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

		$this->assertTagNames($posts[0], [$targetTag]);
		$this->assertTagNames($posts[1], [$randomTag]);
		$this->assertTagNames($posts[2], [$randomTag, $targetTag]);

		$this->assertTagNames($posts[3], [$targetTag]);
		$this->assertTagNames($posts[4], [$randomTag, $targetTag]);
		$this->assertTagNames($posts[5], [$randomTag, $targetTag]);
	}

	public function testMergingToNonExistingTag()
	{
		$this->grantAccess('mergeTags');

		$sourceTag = $this->mockTag();
		$post = $this->mockPost($this->mockUser());
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

		$sourceTag = $this->mockTag();
		$post = $this->mockPost($this->mockUser());
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
