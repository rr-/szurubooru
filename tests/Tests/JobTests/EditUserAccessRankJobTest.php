<?php
class EditUserAccessRankJobTest extends AbstractTest
{
	public function testEditing()
	{
		$this->grantAccess('changeUserAccessRank');
		$user = $this->userMocker->mockSingle();

		$this->assert->areEqual(AccessRank::Registered, $user->getAccessRank()->toInteger());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserAccessRankJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_ACCESS_RANK => AccessRank::PowerUser,
				]);
		});

		$this->assert->areEqual(AccessRank::PowerUser, $user->getAccessRank()->toInteger());
	}

	public function testSettingToNobodyDenial()
	{
		$this->grantAccess('changeUserAccessRank');
		$user = $this->userMocker->mockSingle();

		$this->assert->areEqual(AccessRank::Registered, $user->getAccessRank()->toInteger());

		$this->assert->throws(function() use ($user)
		{
			Api::run(
				new EditUserAccessRankJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_ACCESS_RANK => AccessRank::Nobody,
				]);
		}, 'Cannot set special access rank');
	}

	public function testHigherThanMyselfDenial()
	{
		getConfig()->privileges->changeUserAccessRank = 'power-user';
		Access::init();

		$user = $this->userMocker->mockSingle();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		UserModel::save($user);

		$this->assert->areEqual(AccessRank::PowerUser, $user->getAccessRank()->toInteger());

		$this->assert->throws(function() use ($user)
		{
			Api::run(
				new EditUserAccessRankJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_ACCESS_RANK => AccessRank::Admin,
				]);
		}, 'Insufficient privileges');
	}
}
