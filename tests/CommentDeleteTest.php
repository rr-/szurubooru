<?php
class CommentDeleteTest extends AbstractTest
{
	public function testOwn()
	{
		$this->prepare();

		$this->assert->doesNotThrow(function()
		{
			return $this->runApi();
		});

		$this->assert->areEqual(0, CommentModel::getCount());
	}

	public function testNoAuth()
	{
		$this->prepare();
		Auth::setCurrentUser(null);

		$this->assert->throws(function()
		{
			$this->assert->isFalse(Auth::isLoggedIn());
			return $this->runApi();
		}, 'Not logged in');
	}

	public function testOwnAccessDenial()
	{
		$this->prepare();

		getConfig()->privileges->{'deleteComment.own'} = 'nobody';
		Access::init();
		$this->assert->isFalse(Access::check(new Privilege(Privilege::DeleteComment)));

		$this->assert->throws(function()
		{
			return $this->runApi();
		}, 'Insufficient privileges');
	}

	public function testOtherAccessGrant()
	{
		$this->prepare();

		getConfig()->privileges->{'deleteComment.all'} = 'nobody';
		Access::init();
		$this->assert->isTrue(Access::check(new Privilege(Privilege::DeleteComment)));

		$this->assert->doesNotThrow(function()
		{
			return $this->runApi();
		});
	}

	public function testOtherAccessDenial()
	{
		$this->prepare();

		$comment = $this->mockComment(Auth::getCurrentUser());

		//login as someone else
		$this->login($this->mockUser());

		getConfig()->privileges->{'deleteComment.all'} = 'nobody';
		Access::init();
		$this->assert->isTrue(Access::check(new Privilege(Privilege::DeleteComment)));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::DeleteComment, 'own')));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::DeleteComment, 'all')));

		$this->assert->throws(function() use ($comment)
		{
			Api::run(
				new DeleteCommentJob(),
				[
					DeleteCommentJob::COMMENT_ID => $comment->getId(),
				]);
		}, 'Insufficient privileges');
	}

	public function testWrongCommentId()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			return Api::run(
				new DeleteCommentJob(),
				[
					DeleteCommentJob::COMMENT_ID => 100,
				]);
		}, 'Invalid comment ID');
	}


	protected function runApi()
	{
		$comment = $this->mockComment(Auth::getCurrentUser());

		return Api::run(
			new DeleteCommentJob(),
			[
				DeleteCommentJob::COMMENT_ID => $comment->getId(),
			]);
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
