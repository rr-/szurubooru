<?php
class CommentListTest extends AbstractTest
{
	public function testNone()
	{
		$this->assert->areEqual(0, CommentModel::getCount());

		$ret = $this->runApi(1);
		$this->assert->areEqual(0, count($ret->entities));
	}

	public function testSingle()
	{
		$this->assert->areEqual(0, CommentModel::getCount());

		$this->mockComment($this->mockUser());

		$ret = $this->runApi(1);
		$this->assert->areEqual(1, count($ret->entities));

		$post = $ret->entities[0];
		$samePost = $this->assert->doesNotThrow(function() use ($post)
		{
			return PostModel::findById($post->getId());
		});
		//posts retrieved via ListCommentsJob should already have cached its comments
		$this->assert->areNotEquivalent($post, $samePost);
		$post->resetCache();
		$samePost->resetCache();
		$this->assert->areEquivalent($post, $samePost);
	}

	public function testPaging()
	{
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

	public function testAccessDenial()
	{
		getConfig()->privileges->listComments = 'nobody';
		Access::init();
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListComments)));

		$this->assert->throws(function()
		{
			$this->runApi(1);
		}, 'Insufficient privileges');
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
