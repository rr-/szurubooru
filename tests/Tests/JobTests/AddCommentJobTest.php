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
		$post = $comment->getPost();

		$this->assert->areEqual(1, CommentModel::getCount());
		$this->assert->areEqual($text, $comment->getText());
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $comment->getCommenter()->getId());
		$this->assert->areEqual(1, $post->getId());
		$this->assert->areEqual(1, $post->getCommentCount());
		$this->assert->isNotNull($comment->getCreationTime());
		$this->assert->doesNotThrow(function() use ($comment)
		{
			CommentModel::getById($comment->getId());
		});
	}

	public function testAlmostTooShortText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->minLength));
		});
	}

	public function testAlmostTooLongText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->maxLength));
		});
	}

	public function testTooShortText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->minLength - 1));
		}, 'Comment must have at least');
	}

	public function testTooLongText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->maxLength + 1));
		}, 'Comment must have at most');
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
					JobArgs::ARG_POST_ID => 100,
					JobArgs::ARG_NEW_TEXT => 'alohaa',
				]);
		}, 'Invalid post ID');
	}


	protected function runApi($text)
	{
		$post = $this->postMocker->mockSingle();

		return Api::run(
			new AddCommentJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_NEW_TEXT => $text,
			]);
	}

	protected function prepare()
	{
		Core::getConfig()->registration->needEmailForCommenting = false;
		$this->grantAccess('addComment');
		$this->login($this->userMocker->mockSingle());
	}
}
