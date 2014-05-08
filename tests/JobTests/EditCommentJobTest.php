<?php
class EditCommentJobTest extends AbstractTest
{
	public function testOwn()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual($text, $comment->getText());
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $comment->getCommenter()->getId());
		$this->assert->areEqual(1, $comment->getPost()->getId());
		$this->assert->isNotNull($comment->getCreationTime());
		$this->assert->doesNotThrow(function() use ($comment)
		{
			CommentModel::getById($comment->getId());
		});
	}

	public function testOwnAlmostTooShortText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->minLength));
		});
	}

	public function testOwnAlmostTooLongText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->maxLength));
		});
	}

	public function testOwnTooShortText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->minLength - 1));
		}, 'Comment must have at least');
	}

	public function testOwnTooLongText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', getConfig()->comments->maxLength + 1));
		}, 'Comment must have at most');
	}

	public function testWrongCommentId()
	{
		$this->prepare();
		$this->assert->throws(function()
		{
			Api::run(
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
				EditCommentJob::COMMENT_ID => $comment->getId(),
				EditCommentJob::TEXT => $text,
			]);
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}
}
