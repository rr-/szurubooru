<?php
class SafePostRetrieverTest extends AbstractTest
{
	public function testRetrievingById()
	{
		$post = $this->preparePost();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_POST_ID, $post->getId());
		$this->assertIncorrectRetrieval($retriever);
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

	public function testRetrievingByNonExistingName()
	{
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_POST_NAME, 'nonsense');
		$this->assert->throws(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		}, 'Invalid post name');
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
				JobArgs::ARG_POST_NAME,
				JobArgs::ARG_POST_ENTITY),
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
		return $this->postMocker->mockSingle();
	}

	private function prepareRetriever()
	{
		$job = new EditPostJob();
		$postRetriever = new SafePostRetriever($job);
		return $postRetriever;
	}
}
