<?php
class DeleteCommentJobTest extends AbstractTest
{
	public function testOwn()
	{
		$this->prepare();
		$this->grantAccess('deleteComment');

		$this->assert->doesNotThrow(function()
		{
			$this->runApi();
		});

		$this->assert->areEqual(0, CommentModel::getCount());
	}

	public function testNoAuth()
	{
		$this->prepare();
		Auth::setCurrentUser(null);

		$this->assert->throws(function()
		{
			$this->runApi();
		}, 'Not logged in');
	}

	public function testOwnAccessDenial()
	{
		$this->prepare();

		$this->assert->throws(function()
		{
			$this->runApi();
		}, 'Insufficient privileges');
	}

	public function testOtherAccessGrant()
	{
		$this->prepare();
		$this->grantAccess('deleteComment.all');

		$comment = $this->mockComment(Auth::getCurrentUser());
		//login as someone else
		$this->login($this->mockUser());

		$this->assert->doesNotThrow(function() use ($comment)
		{
			$this->runApi($comment);
		});
	}

	public function testOtherAccessDenial()
	{
		$this->prepare();
		$this->grantAccess('deleteComment.own');

		$comment = $this->mockComment(Auth::getCurrentUser());
		//login as someone else
		$this->login($this->mockUser());

		$this->assert->throws(function() use ($comment)
		{
			$this->runApi($comment);
		}, 'Insufficient privileges');
	}

	public function testWrongCommentId()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			Api::run(
				new DeleteCommentJob(),
				[
					DeleteCommentJob::COMMENT_ID => 100,
				]);
		}, 'Invalid comment ID');
	}


	protected function runApi($comment = null)
	{
		if ($comment === null)
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
