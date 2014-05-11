<?php
class PreviewCommentJobTest extends AbstractTest
{
	public function testPreview()
	{
		$this->prepare();

		$text = 'alohaaaaaaa';
		$comment = $this->assert->doesNotThrow(function() use ($text)
		{
			return $this->runApi($text);
		});

		$this->assert->areEqual(0, CommentModel::getCount());
		$this->assert->areEqual($text, $comment->getText());
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $comment->getCommenter()->getId());
		$this->assert->isNotNull($comment->getCreationTime());
		$this->assert->throws(function() use ($comment)
		{
			CommentModel::getById($comment->getId());
		}, 'Invalid comment ID');
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

	public function testViaPost()
	{
		$this->prepare();
		$post = $this->mockPost(Auth::getCurrentUser());

		$this->assert->doesNotThrow(function() use ($post)
		{
			Api::run(
				new PreviewCommentJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TEXT => 'alohaaa',
				]);
		});
	}

	public function testViaComment()
	{
		$this->prepare();
		$comment = $this->mockComment(Auth::getCurrentUser());

		$this->assert->doesNotThrow(function() use ($comment)
		{
			Api::run(
				new PreviewCommentJob(),
				[
					JobArgs::ARG_COMMENT_ID => $comment->getId(),
					JobArgs::ARG_NEW_TEXT => 'alohaaa',
				]);
		});
	}


	protected function runApi($text)
	{
		$post = $this->mockPost(Auth::getCurrentUser());

		return Api::run(
			new PreviewCommentJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_NEW_TEXT => $text,
			]);
	}

	protected function prepare()
	{
		getConfig()->registration->needEmailForCommenting = false;
		$this->grantAccess('addComment');
		$this->login($this->mockUser());
	}
}
