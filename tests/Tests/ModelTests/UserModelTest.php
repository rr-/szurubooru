<?php
class UserModelTest extends AbstractTest
{
	public function testSavingTwoUsersSameNameNoActivation()
	{
		Core::getConfig()->registration->needEmailForRegistering = false;
		list ($user1, $user2) = $this->prepareTwoUsersWithSameName();
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this name is already registered');
	}

	public function testSavingTwoUsersSameNameEmailActivation()
	{
		Core::getConfig()->registration->needEmailForRegistering = true;
		list ($user1, $user2) = $this->prepareTwoUsersWithSameName();
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this name is already registered and awaits e-mail');
	}

	public function testSavingTwoUsersSameNameStaffActivation()
	{
		Core::getConfig()->registration->staffActivation = true;
		list ($user1, $user2) = $this->prepareTwoUsersWithSameName();
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this name is already registered and awaits staff');
	}

	public function testSavingTwoUsersSameEmailNoActivation()
	{
		Core::getConfig()->registration->needEmailForRegistering = false;
		list ($user1, $user2) = $this->prepareTwoUsersWithSameEmail(false, false);
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this e-mail is already registered');
	}

	public function testSavingTwoUsersSameEmailEmailActivation()
	{
		Core::getConfig()->registration->needEmailForRegistering = true;
		list ($user1, $user2) = $this->prepareTwoUsersWithSameEmail(false, false);
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this e-mail is already registered and awaits e-mail');
	}

	public function testSavingTwoUsersSameEmailStaffActivation()
	{
		Core::getConfig()->registration->staffActivation = true;
		list ($user1, $user2) = $this->prepareTwoUsersWithSameEmail(false, false);
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this e-mail is already registered and awaits staff');
	}

	public function testSavingTwoUsersSameEmailDifferConfirm1()
	{
		list ($user1, $user2) = $this->prepareTwoUsersWithSameEmail(true, false);
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this e-mail is already registered');
	}

	public function testSavingTwoUsersSameEmailDifferConfirm2()
	{
		list ($user1, $user2) = $this->prepareTwoUsersWithSameEmail(false, true);
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this e-mail is already registered');
	}

	public function testSavingTwoUsersSameEmailDifferConfirm3()
	{
		list ($user1, $user2) = $this->prepareTwoUsersWithSameEmail(true, true);
		$this->assert->throws(function() use ($user2)
		{
			UserModel::save($user2);
		}, 'User with this e-mail is already registered');
	}

	private function prepareTwoUsersWithSameName()
	{
		list ($user1, $user2) = $this->userMocker->mockMultiple(2);
		$user1->setName('pikachu');
		$user2->setName('pikachu');
		UserModel::save($user1);
		return [$user1, $user2];
	}

	private function prepareTwoUsersWithSameEmail($confirmFirst, $confirmSecond)
	{
		list ($user1, $user2) = $this->userMocker->mockMultiple(2);
		$mail = 'godzilla@whitestar.gov';

		if ($confirmFirst)
			$user1->setConfirmedEmail($mail);
		else
			$user1->setUnconfirmedEmail($mail);

		if ($confirmFirst)
			$user2->setConfirmedEmail($mail);
		else
			$user2->setUnconfirmedEmail($mail);

		UserModel::save($user1);
		return [$user1, $user2];
	}
}
