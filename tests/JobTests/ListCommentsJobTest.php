<?php
class ListCommentJobTest extends AbstractTest
{
	public function testNone()
	{
		$this->grantAccess('listComments');

		$this->assert->areEqual(0, CommentModel::getCount());

		$ret = $this->runApi(1);
		$this->assert->areEqual(0, count($ret->entities));
	}

	public function testSingle()
	{
		$this->grantAccess('listComments');
		$this->grantAccess('listPosts');

		$this->assert->areEqual(0, CommentModel::getCount());

		$comment = $this->mockComment($this->mockUser());

		$ret = $this->runApi(1);
		$this->assert->areEqual(1, count($ret->entities));

		$post = $ret->entities[0];
		$newComment = $post->getComments()[0];
		$this->assert->areEqual($comment->getPostId(), $newComment->getPostId());
		$this->assert->areEqual($comment->getPost()->getId(), $newComment->getPost()->getId());

		$samePost = $this->assert->doesNotThrow(function() use ($post)
		{
			return PostModel::getById($post->getId());
		});
		//posts retrieved via ListCommentsJob should already have cached its comments
		$this->assert->areNotEquivalent($post, $samePost);
		$post->resetCache();
		$samePost->resetCache();
		$this->assert->areEquivalent($post, $samePost);
	}

	public function testPaging()
	{
		$this->grantAccess('listComments');
		$this->grantAccess('listPosts');

		getConfig()->comments->commentsPerPage = 2;

		$this->assert->areEqual(0, CommentModel::getCount());

		$this->mockComment($this->mockUser());
		$this->mockComment($this->mockUser());
		$this->mockComment($this->mockUser());

		$ret = $this->runApi(1);
		$this->assert->areEqual(2, count($ret->entities));

		$ret = $this->runApi(2);
		$this->assert->areEqual(1, count($ret->entities));
	}

	protected function runApi($page)
	{
		return Api::run(
			new ListCommentsJob(),
			[
				ListCommentsJob::PAGE_NUMBER => $page,
			]);
	}
}
