<?php
class PostRetrieverTest extends AbstractTest
{
	public function testRetrievingById()
	{
		$post = $this->preparePost();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_POST_ID, $post->getId());
		$this->assertCorrectRetrieval($retriever, $post);
	}

	public function testRetrievingByName()
	{
		$post = $this->preparePost();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_POST_NAME, $post->getName());
		$this->assertCorrectRetrieval($retriever, $post);
	}

	public function testRetrievingByEntity()
	{
		$post = $this->preparePost();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_POST_ENTITY, $post);
		$this->assertCorrectRetrieval($retriever, $post);
	}

	public function testRetrievingByNonExistingId()
	{
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_POST_ID, 100);
		$this->assert->throws(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		}, 'Invalid post ID');
	}

	public function testRetrievingNoArguments()
	{
		$retriever = $this->prepareRetriever();
		$this->assertIncorrectRetrieval($retriever);
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

	public function testArgumentRequirements()
	{
		$retriever = $this->prepareRetriever();
		$this->assert->areEquivalent(
			JobArgs::Alternative(
				JobArgs::ARG_POST_NAME,
				JobArgs::ARG_POST_ID,
				JobArgs::ARG_POST_ENTITY),
			$retriever->getRequiredArguments());
	}

	private function assertCorrectRetrieval($retriever, $post)
	{
		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		});
		$this->assert->isNotNull($retriever->tryRetrieve());
		$this->assert->areEqual($post->getId(), $retriever->tryRetrieve()->getId());

		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->retrieve();
		});
		$this->assert->areEqual($post->getId(), $retriever->retrieve()->getId());
	}

	private function preparePost()
	{
		return $this->mockPost($this->mockUser());
	}

	private function prepareRetriever()
	{
		$job = new EditPostJob();
		$postRetriever = new PostRetriever($job);
		return $postRetriever;
	}
}
