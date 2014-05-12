<?php
class DeleteCommentJobTest extends AbstractTest
{
	public function testRemoval()
	{
		$this->prepare();
		$this->grantAccess('deleteComment');

		$comment = $this->mockComment(Auth::getCurrentUser());
		$post = $comment->getPost();
		$this->assert->areEqual(1, $post->getCommentCount());

		$this->assert->doesNotThrow(function() use ($comment)
		{
			Api::run(
				new DeleteCommentJob(),
				[
					JobArgs::ARG_COMMENT_ID => $comment->getId(),
				]);
		});

		//post needs to be retrieved again from db to refresh cache
		$post = PostModel::getById($post->getId());
		$this->assert->areEqual(0, $post->getCommentCount());
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
					JobArgs::ARG_COMMENT_ID => 100,
				]);
		}, 'Invalid comment ID');
	}


	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
