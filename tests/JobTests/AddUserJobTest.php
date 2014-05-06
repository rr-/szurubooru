<?php
class AddUserJobTest extends AbstractTest
{
	public function testSaving()
	{
		$this->grantAccess('registerAccount');

		$user1 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy',
					EditUserPasswordJob::NEW_PASSWORD => 'sekai',
				]);
		});

		$this->assert->areEqual('dummy', $user1->getName());
		$this->assert->areEquivalent(new AccessRank(AccessRank::Admin), $user1->getAccessRank());
		$this->assert->isFalse(empty($user1->getPasswordSalt()));
		$this->assert->isFalse(empty($user1->getPasswordHash()));

		$user2 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy2',
					EditUserPasswordJob::NEW_PASSWORD => 'sekai',
				]);
		});

		$this->assert->areEquivalent(new AccessRank(AccessRank::Registered), $user2->getAccessRank());
	}

	public function testTooShortPassword()
	{
		$this->grantAccess('registerAccount');

		$this->assert->throws(function()
		{
			Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy',
					EditUserPasswordJob::NEW_PASSWORD => str_repeat('s', getConfig()->registration->passMinLength - 1),
				]);
		}, 'Password must have at least');
	}

	public function testVeryLongPassword()
	{
		$this->grantAccess('registerAccount');

		$pass = str_repeat('s', 10000);
		$user = $this->assert->doesNotThrow(function() use ($pass)
		{
			return Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy',
					EditUserPasswordJob::NEW_PASSWORD => $pass,
				]);
		});

		$this->assert->isTrue(strlen($user->getPasswordHash()) < 100);

		getConfig()->registration->needEmailForRegistering = false;
		$this->assert->doesNotThrow(function() use ($pass)
		{
			Auth::login('dummy', $pass, false);
		});
		$this->assert->throws(function() use ($pass)
		{
			Auth::login('dummy', $pass . '!', false);
		}, 'Invalid password');
	}

	public function testDuplicateNames()
	{
		$this->grantAccess('registerAccount');

		$this->assert->doesNotThrow(function()
		{
			Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy',
					EditUserPasswordJob::NEW_PASSWORD => 'sekai',
				]);
		});

		$this->assert->throws(function()
		{
			Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy',
					EditUserPasswordJob::NEW_PASSWORD => 'sekai',
				]);
		}, 'User with');
	}

	public function testAccessRankDenial()
	{
		$this->grantAccess('registerAccount');

		$this->assert->throws(function()
		{
			Api::run(
				new AddUserJob(),
				[
					EditUserNameJob::NEW_USER_NAME => 'dummy',
					EditUserPasswordJob::NEW_PASSWORD => 'sekai',
					EditUserAccessRankJob::NEW_ACCESS_RANK => 'power-user',
				]);
		}, 'Insufficient privileges');
	}
}
