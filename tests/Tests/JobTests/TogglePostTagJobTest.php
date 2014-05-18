<?php
class TogglePostTagJobTest extends AbstractTest
{
	public function testEnabling()
	{
		$post = $this->preparePost(['test']);

		$this->assertHasTag($post, 'test');
		$this->assertDoesNotHaveTag($post, 'test2');

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return $this->addTag($post, 'test2');
		});

		$this->assertHasTag($post, 'test');
		$this->assertHasTag($post, 'test2');
	}

	public function testEnablingAlreadyEnabled()
	{
		$post = $this->preparePost(['test']);

		$this->assertHasTag($post, 'test');
		$this->assertDoesNotHaveTag($post, 'test2');

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return $this->addTag($post, 'test2');
		});
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return $this->addTag($post, 'test2');
		});

		$this->assertHasTag($post, 'test');
		$this->assertHasTag($post, 'test2');
	}

	public function testDisabling()
	{
		$post = $this->preparePost(['test', 'test2']);

		$this->assertHasTag($post, 'test');
		$this->assertHasTag($post, 'test2');

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return $this->removeTag($post, 'test2');
		});

		$this->assertHasTag($post, 'test');
		$this->assertDoesNotHaveTag($post, 'test2');
	}

	public function testDisablingAlreadyDisabled()
	{
		$post = $this->preparePost(['test', 'test2']);

		$this->assertHasTag($post, 'test');
		$this->assertHasTag($post, 'test2');

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return $this->removeTag($post, 'test2');
		});
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return $this->removeTag($post, 'test2');
		});

		$this->assertHasTag($post, 'test');
		$this->assertDoesNotHaveTag($post, 'test2');
	}

	public function testDisablingLastTag()
	{
		$post = $this->preparePost(['test']);

		$this->assertHasTag($post, 'test');

		$this->assert->throws(function() use ($post)
		{
			$this->removeTag($post, 'test');
		}, 'No tags set');

		$this->assertHasTag($post, 'test');
	}

	private function addTag($post, $tagName)
	{
		return Api::run(
			new TogglePostTagJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_TAG_NAME => $tagName,
				JobArgs::ARG_NEW_STATE => true,
			]);
	}

	private function removeTag($post, $tagName)
	{
		return Api::run(
			new TogglePostTagJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_TAG_NAME => $tagName,
				JobArgs::ARG_NEW_STATE => false,
			]);
	}

	private function assertDoesNotHaveTag($post, $tagName)
	{
		$tagNames = $this->getTagNames($post);
		$this->assert->isFalse(in_array($tagName, $tagNames));
	}

	private function assertHasTag($post, $tagName)
	{
		$tagNames = $this->getTagNames($post);
		$this->assert->isTrue(in_array($tagName, $tagNames));
	}

	private function preparePost(array $tagNames)
	{
		$this->grantAccess('editPostTags');
		$post = $this->postMocker->mockSingle();
		$post->setTags(TagModel::spawnFromNames($tagNames));
		PostModel::save($post);
		return $post;
	}

	private function getTagNames($post)
	{
		return array_map(
			function($tag)
			{
				return $tag->getName();
			}, $post->getTags());
	}
}
