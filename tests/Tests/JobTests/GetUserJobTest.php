<?php
class GetUserJobTest extends AbstractTest
{
	public function testUserRetrieval()
	{
		$this->grantAccess('viewUser');
		$user = $this->userMocker->mockSingle();

		//reload from model to make sure it's same
		$user = UserModel::getById($user->getId());

		$sameUser = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new GetUserJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$user->resetCache();
		$sameUser->resetCache();
		$this->assert->areEquivalent($user, $sameUser);
	}

	public function testInvalidId()
	{
		$this->grantAccess('viewUser');

		$this->assert->throws(function()
		{
			Api::run(
				new GetUserJob(),
				[
					JobArgs::ARG_USER_NAME => 'nonsense',
				]);
		}, 'Invalid user name');
	}
}
