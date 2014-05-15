<?php
class EditPostTagsJobTest extends AbstractTest
{
	public function testEditing()
	{
		$post = $this->postMocker->mockSingle();
		$this->grantAccess('editPostTags');

		$newTagNames = ['big', 'boss'];
		$post = $this->assert->doesNotThrow(function() use ($post, $newTagNames)
		{
			return Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => $newTagNames,
				]);
		});

		$receivedTagNames = array_map(function($tag)
		{
			return $tag->getName();
		}, $post->getTags());

		natcasesort($receivedTagNames);
		natcasesort($newTagNames);

		$this->assert->areEquivalent($newTagNames, $receivedTagNames);
	}

	public function testFailOnEmptyTags()
	{
		$post = $this->postMocker->mockSingle();
		$this->grantAccess('editPostTags');

		$this->assert->throws(function() use ($post)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => [],
				]);
		}, 'No tags set');
	}

	public function testTooShortTag()
	{
		$post = $this->postMocker->mockSingle();
		$this->grantAccess('editPostTags');

		$newTagNames = [str_repeat('u', Core::getConfig()->tags->minLength - 1)];
		$this->assert->throws(function() use ($post, $newTagNames)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => $newTagNames,
				]);
		}, 'Tag must have at least');
	}

	public function testTooLongTag()
	{
		$post = $this->postMocker->mockSingle();
		$this->grantAccess('editPostTags');

		$newTagNames = [str_repeat('u', Core::getConfig()->tags->maxLength + 1)];
		$this->assert->throws(function() use ($post, $newTagNames)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => $newTagNames,
				]);
		}, 'Tag must have at most');
	}

	public function testInvalidTag()
	{
		$post = $this->postMocker->mockSingle();
		$this->grantAccess('editPostTags');

		$newTagNames = ['bulma/goku'];
		$this->assert->throws(function() use ($post, $newTagNames)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => $newTagNames,
				]);
		}, 'Invalid tag');
	}

	public function testInvalidPost()
	{
		$this->grantAccess('editPostTags');

		$newTagNames = ['lisa'];
		$this->assert->throws(function() use ($newTagNames)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => 100,
					JobArgs::ARG_NEW_TAG_NAMES => $newTagNames,
				]);
		}, 'Invalid post ID');
	}
}
