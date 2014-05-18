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
		$this->assert->isNotNull($comment->getPost()->getId());
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
			$this->runApi(str_repeat('b', Core::getConfig()->comments->minLength));
		});
	}

	public function testOwnAlmostTooLongText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->doesNotThrow(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->maxLength));
		});
	}

	public function testOwnTooShortText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->minLength - 1));
		}, 'Comment must have at least');
	}

	public function testOwnTooLongText()
	{
		$this->prepare();
		$this->grantAccess('editComment.own');
		$this->assert->throws(function()
		{
			$this->runApi(str_repeat('b', Core::getConfig()->comments->maxLength + 1));
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
					JobArgs::ARG_COMMENT_ID => 100,
					JobArgs::ARG_NEW_TEXT => 'alohaa',
				]);
		}, 'Invalid comment ID');
	}


	protected function runApi($text)
	{
		$comment = $this->commentMocker->mockSingle();
		$comment->setCommenter(Auth::getCurrentUser());
		CommentModel::save($comment);

		return Api::run(
			new EditCommentJob(),
			[
				JobArgs::ARG_COMMENT_ID => $comment->getId(),
				JobArgs::ARG_NEW_TEXT => $text,
			]);
	}

	protected function prepare()
	{
		$this->login($this->userMocker->mockSingle());
	}
}
