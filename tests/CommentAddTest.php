<?php
class CommentAddTest extends AbstractTest
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
		$this->assert->areEqual($text, $comment->text);
		$this->assert->areEqual(Auth::getCurrentUser()->id, $comment->getCommenter()->id);
		$this->assert->areEqual(1, $comment->getPost()->id);
		$this->assert->isNotNull($comment->commentDate);
		$this->assert->doesNotThrow(function() use ($comment)
		{
			UserModel::findById($comment->id);
		});
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

	public function testAnonymous()
	{
		$this->prepare();

		Auth::setCurrentUser(null);
		getConfig()->privileges->addComment = 'anonymous';
		Access::init();
		$this->assert->isTrue(Access::check(new Privilege(Privilege::AddComment)));

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual($text, $comment->text);
		$this->assert->areEqual(Auth::getCurrentUser()->id, $comment->getCommenter()->id);
		$this->assert->areEqual(UserModel::getAnonymousName(), $comment->getCommenter()->getName());
	}

	public function testPrivilegeDependancies()
	{
		$this->prepare();

		getConfig()->privileges->{'editComment'} = 'nobody';
		getConfig()->privileges->{'editComment.own'} = 'nobody';
		getConfig()->privileges->{'editComment.all'} = 'nobody';
		Access::init();
		$this->assert->isTrue(Access::check(new Privilege(Privilege::AddComment)));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::EditComment)));

		$this->assert->doesNotThrow(function()
		{
			return $this->runApi('alohaaaaaaa');
		}, 'insufficient privileges');
	}

	public function testWrongPostId()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			return Api::run(
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
				AddCommentJob::POST_ID => $post->id,
				AddCommentJob::TEXT => $text,
			]);
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
