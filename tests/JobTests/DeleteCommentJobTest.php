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
