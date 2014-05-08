<?php
class EditPostSourceJobTest extends AbstractTest
{
	public function testSaving()
	{
		$this->prepare();
		$this->grantAccess('editPostSource.own');
		$post = $this->assert->doesNotThrow(function()
		{
			return $this->runApi('a');
		});

		$this->assert->areEqual('a', $post->getSource());
		$this->assert->doesNotThrow(function() use ($post)
		{
			PostModel::getById($post->getId());
		});
	}

	public function testAlmostTooLongText()
	{
		$this->prepare();
		$this->grantAccess('editPostSource.own');
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('a', getConfig()->posts->maxSourceLength));
		});
	}

	public function testTooLongText()
	{
		$this->prepare();
		$this->grantAccess('editPostSource.own');
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('a', getConfig()->posts->maxSourceLength + 1));
		}, 'Source must have at most');
	}

	public function testWrongPostId()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			Api::run(
				new EditPostSourceJob(),
				[
					EditPostSourceJob::POST_ID => 100,
					EditPostSourceJob::SOURCE => 'alohaa',
				]);
		}, 'Invalid post ID');
	}


	protected function runApi($text)
	{
		$post = $this->mockPost(Auth::getCurrentUser());
		return Api::run(
			new EditPostSourceJob(),
			[
				EditPostSourceJob::POST_ID => $post->getId(),
				EditPostSourceJob::SOURCE => $text
			]);
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
