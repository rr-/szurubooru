<?php
class DeleteUserJobTest extends AbstractTest
{
	public function testRemoval()
	{
		$user = $this->userMocker->mockSingle();
		$this->login($user);
		$this->grantAccess('deleteUser');

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new DeleteUserJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$this->assert->isNull(UserModel::tryGetByName($user->getName()));
		$this->assert->areEqual(0, UserModel::getCount());
	}

	public function testWrongUserId()
	{
		$user = $this->userMocker->mockSingle();
		$this->login($user);

		$this->assert->throws(function()
		{
			Api::run(
				new DeleteUserJob(),
				[
					JobArgs::ARG_USER_NAME => 'robocop',
				]);
		}, 'Invalid user name');
	}
}
