<?php
class PreviewCommentJobTest extends AbstractTest
{
	public function testPreview()
	{
		$this->prepare();

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual(0, CommentModel::getCount());
		$this->assert->areEqual($text, $comment->getText());
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $comment->getCommenter()->getId());
		$this->assert->isNotNull($comment->getDateTime());
		$this->assert->throws(function() use ($comment)
		{
			CommentModel::findById($comment->getId());
		}, 'Invalid comment ID');
	}

	public function testAlmostTooShortText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->minLength));
		});
	}

	public function testAlmostTooLongText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->maxLength));
		});
	}

	public function testTooShortText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->minLength - 1));
		}, 'Comment must have at least');
	}

	public function testTooLongText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->maxLength + 1));
		}, 'Comment must have at most');
	}

	public function testNoAuth()
	{
		$this->prepare();
		Auth::setCurrentUser(null);

		$this->assert->throws(function()
		{
			$this->assert->isFalse(Auth::isLoggedIn());
			return $this->runApi('alohaaaaaaa');
		}, 'Insufficient privileges');
	}

	public function testAccessDenial()
	{
		$this->prepare();

		getConfig()->privileges->addComment = 'nobody';
		Access::init();
		$this->assert->isFalse(Access::check(new Privilege(Privilege::AddComment)));

		$this->assert->throws(function()
		{
			return $this->runApi('alohaaaaaaa');
		}, 'Insufficient privileges');
	}


	protected function runApi($text)
	{
		$post = $this->mockPost();

		return Api::run(
			new PreviewCommentJob(),
			[
				PreviewCommentJob::POST_ID => $post->getId(),
				PreviewCommentJob::TEXT => $text,
			]);
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
