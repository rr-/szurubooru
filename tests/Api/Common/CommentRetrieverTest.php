<?php
class CommentRetrieverTest extends AbstractTest
{
	public function testRetrievingById()
	{
		$comment = $this->prepareComment();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_COMMENT_ID, $comment->getId());
		$this->assertCorrectRetrieval($retriever, $comment);
	}

	public function testRetrievingByEntity()
	{
		$comment = $this->prepareComment();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_COMMENT_ENTITY, $comment);
		$this->assertCorrectRetrieval($retriever, $comment);
	}

	public function testRetrievingByNonExistingId()
	{
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_COMMENT_ID, 100);
		$this->assert->throws(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		}, 'Invalid comment id');
	}

	public function testRetrievingNoArguments()
	{
		$retriever = $this->prepareRetriever();
		$this->assertIncorrectRetrieval($retriever);
	}

	public function testArgumentRequirements()
	{
		$retriever = $this->prepareRetriever();
		$this->assert->areEquivalent(
			JobArgs::Alternative(
				JobArgs::ARG_COMMENT_ID,
				JobArgs::ARG_COMMENT_ENTITY),
			$retriever->getRequiredArguments());
	}

	private function assertIncorrectRetrieval($retriever)
	{
		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		});
		$this->assert->isNull($retriever->tryRetrieve());

		$this->assert->throws(function() use ($retriever)
		{
			$retriever->retrieve();
		}, 'unsatisfied');
	}

	private function assertCorrectRetrieval($retriever, $comment)
	{
		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		});
		$this->assert->isNotNull($retriever->tryRetrieve());
		$this->assert->areEqual($comment->getId(), $retriever->tryRetrieve()->getId());

		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->retrieve();
		});
		$this->assert->areEqual($comment->getId(), $retriever->retrieve()->getId());
	}

	private function prepareComment()
	{
		return $this->mockComment($this->mockUser());
	}

	private function prepareRetriever()
	{
		$job = new EditCommentJob();
		$commentRetriever = new CommentRetriever($job);
		return $commentRetriever;
	}
}
