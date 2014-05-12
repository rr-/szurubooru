<?php
class UserRetrieverTest extends AbstractTest
{
	public function testRetrievingByName()
	{
		$user = $this->prepareUser();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_USER_NAME, $user->getName());
		$this->assertCorrectRetrieval($retriever, $user);
	}

	public function testRetrievingByEmail()
	{
		$user = $this->prepareUser();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_USER_EMAIL, $user->getConfirmedEmail());
		$this->assertCorrectRetrieval($retriever, $user);
	}

	public function testRetrievingByEntity()
	{
		$user = $this->prepareUser();
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_USER_ENTITY, $user);
		$this->assertCorrectRetrieval($retriever, $user);
	}

	public function testRetrievingByNonExistingName()
	{
		$retriever = $this->prepareRetriever();
		$retriever->getJob()->setArgument(JobArgs::ARG_USER_NAME, 100);
		$this->assert->throws(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		}, 'Invalid user name');
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
				JobArgs::ARG_USER_NAME,
				JobArgs::ARG_USER_EMAIL,
				JobArgs::ARG_USER_ENTITY),
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

	private function assertCorrectRetrieval($retriever, $user)
	{
		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->tryRetrieve();
		});
		$this->assert->isNotNull($retriever->tryRetrieve());
		$this->assert->areEqual($user->getId(), $retriever->tryRetrieve()->getId());

		$this->assert->doesNotThrow(function() use ($retriever)
		{
			$retriever->retrieve();
		});
		$this->assert->areEqual($user->getId(), $retriever->retrieve()->getId());
	}

	private function prepareUser()
	{
		$user = $this->mockUser();
		$user->setConfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);
		return $user;
	}

	private function prepareRetriever()
	{
		$job = new EditUserJob();
		$userRetriever = new UserRetriever($job);
		return $userRetriever;
	}
}

