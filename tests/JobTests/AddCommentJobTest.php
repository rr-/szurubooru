<?php
class AddCommentJobTest extends AbstractTest
{
	protected $user;

	public function testSaving()
	{
		$this->prepare();

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual(1, CommentModel::getCount());
		$this->assert->areEqual($text, $comment->getText());
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $comment->getCommenter()->getId());
		$this->assert->areEqual(1, $comment->getPost()->getId());
		$this->assert->isNotNull($comment->getDateTime());
		$this->assert->doesNotThrow(function() use ($comment)
		{
			CommentModel::findById($comment->getId());
		});
	}

	public function testEmailActivation()
	{
		$this->prepare();
		getConfig()->registration->needEmailForCommenting = true;
		$this->assert->throws(function()
		{
			$this->runApi('alohaaaa');
		}, 'Need e-mail');
	}

	public function testAlmostTooShortText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->minLength));
		});
	}

	public function testAlmostTooLongText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->maxLength));
		});
	}

	public function testTooShortText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->minLength - 1));
		}, 'Comment must have at least');
	}

	public function testTooLongText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->maxLength + 1));
		}, 'Comment must have at most');
	}

	public function testNoAuth()
	{
		$this->prepare();
		Auth::setCurrentUser(null);

		$this->assert->doesNotThrow(function()
		{
			$this->runApi('alohaaaaaaa');
		});
	}

	public function testAccessDenial()
	{
		$this->prepare();
		$this->revokeAccess('addComment');

		$this->assert->throws(function()
		{
			$this->runApi('alohaaaaaaa');
		}, 'Insufficient privileges');
	}

	public function testAnonymous()
	{
		$this->prepare();
		$this->grantAccess('addComment');
		Auth::setCurrentUser(null);

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual($text, $comment->getText());
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $comment->getCommenter()->getId());
		$this->assert->areEqual(UserModel::getAnonymousName(), $comment->getCommenter()->getName());
	}

	public function testWrongPostId()
	{
		$this->prepare();
		$this->grantAccess('addComment');
		$this->assert->throws(function()
		{
			Api::run(
				new AddCommentJob(),
				[
					AddCommentJob::POST_ID => 100,
					AddCommentJob::TEXT => 'alohaa',
				]);
		}, 'Invalid post ID');
	}


	protected function runApi($text)
	{
		$post = $this->mockPost();

		return Api::run(
			new AddCommentJob(),
			[
				AddCommentJob::POST_ID => $post->getId(),
				AddCommentJob::TEXT => $text,
			]);
	}

	protected function prepare()
	{
		getConfig()->registration->needEmailForCommenting = false;
		$this->grantAccess('addComment');
		$this->login($this->mockUser());
	}
}
