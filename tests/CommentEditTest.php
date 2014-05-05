<?php
class CommentEditTest extends AbstractTest
{
	public function testOwn()
	{
		$this->prepare();

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual($text, $comment->text);
		$this->assert->areEqual(Auth::getCurrentUser()->id, $comment->getCommenter()->id);
		$this->assert->areEqual(1, $comment->getPost()->id);
		$this->assert->isNotNull($comment->commentDate);
		$this->assert->doesNotThrow(function() use ($comment)
		{
			UserModel::findById($comment->id);
		});
	}

	public function testOwnAlmostTooShortText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->minLength));
		});
	}

	public function testOwnAlmostTooLongText()
	{
		$this->prepare();
		$this->assert->doesNotThrow(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->maxLength));
		});
	}

	public function testOwnTooShortText()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			return $this->runApi(str_repeat('b', getConfig()->comments->minLength - 1));
		}, 'Comment must have at least');
	}

	public function testOwnTooLongText()
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
		}, 'Not logged in');
	}

	public function testOwnAccessDenial()
	{
		$this->prepare();

		getConfig()->privileges->{'editComment.own'} = 'nobody';
		Access::init();
		$this->assert->isFalse(Access::check(new Privilege(Privilege::EditComment)));

		$this->assert->throws(function()
		{
			return $this->runApi('alohaaaaaaa');
		}, 'Insufficient privileges');
	}

	public function testOtherAccessGrant()
	{
		$this->prepare();

		getConfig()->privileges->{'editComment.all'} = 'nobody';
		Access::init();
		$this->assert->isTrue(Access::check(new Privilege(Privilege::EditComment)));

		$this->assert->doesNotThrow(function()
		{
			return $this->runApi('alohaaaaaaa');
		});
	}

	public function testOtherAccessDenial()
	{
		$this->prepare();

		$comment = $this->mockComment(Auth::getCurrentUser());

		//login as someone else
		$this->login($this->mockUser());

		getConfig()->privileges->{'editComment.all'} = 'nobody';
		Access::init();
		$this->assert->isTrue(Access::check(new Privilege(Privilege::EditComment)));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::EditComment, 'own')));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::EditComment, 'all')));

		$this->assert->throws(function() use ($comment)
		{
			Api::run(
				new EditCommentJob(),
				[
					EditCommentJob::COMMENT_ID => $comment->id,
					EditCommentJob::TEXT => 'alohaa',
				]);
		}, 'Insufficient privileges');
	}

	public function testWrongCommentId()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			return Api::run(
				new EditCommentJob(),
				[
					EditCommentJob::COMMENT_ID => 100,
					EditCommentJob::TEXT => 'alohaa',
				]);
		}, 'Invalid comment ID');
	}


	protected function runApi($text)
	{
		$comment = $this->mockComment(Auth::getCurrentUser());

		return Api::run(
			new EditCommentJob(),
			[
				EditCommentJob::COMMENT_ID => $comment->id,
				EditCommentJob::TEXT => $text,
			]);
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
