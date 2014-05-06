<?php
class AuthTest extends AbstractTest
{
	public function testValidPassword()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		UserModel::save($user);

		$this->assert->doesNotThrow(function()
		{
			Auth::login('existing', 'ble', false);
		});
	}

	public function testLogout()
	{
		$this->assert->isFalse(Auth::isLoggedIn());
		$this->testValidPassword();
		$this->assert->isTrue(Auth::isLoggedIn());
		Auth::setCurrentUser(null);
		$this->assert->isFalse(Auth::isLoggedIn());
	}

	public function testInvalidUser()
	{
		$this->assert->throws(function()
		{
			Auth::login('non-existing', 'wrong-password', false);
		}, 'invalid username');
	}

	public function testInvalidPassword()
	{
		$user = $this->prepareValidUser();
		$user->passHash = UserModel::hashPassword('ble2', $user->passSalt);
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'wrong-password', false);
		}, 'invalid password');
	}

	public function testBanned()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		$user->ban();
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'ble', false);
		}, 'You are banned');
	}

	public function testStaffConfirmationEnabled()
	{
		getConfig()->registration->staffActivation = true;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		$user->staffConfirmed = false;
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'ble', false);
		}, 'staff hasn\'t confirmed');
	}

	public function testStaffConfirmationDisabled()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		$user->staffConfirmed = false;
		UserModel::save($user);

		$this->assert->doesNotThrow(function()
		{
			Auth::login('existing', 'ble', false);
		});
	}

	public function testMailConfirmationEnabledFail1()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = true;

		$user = $this->prepareValidUser();
		$user->staffConfirmed = false;
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'ble', false);
		}, 'need e-mail address confirmation');
	}

	public function testMailConfirmationEnabledFail2()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = true;

		$user = $this->prepareValidUser();
		$user->staffConfirmed = false;
		$user->emailUnconfirmed = 'test@example.com';
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'ble', false);
		}, 'need e-mail address confirmation');
	}

	public function testMailConfirmationEnabledPass()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = true;

		$user = $this->prepareValidUser();
		$user->staffConfirmed = false;
		$user->emailConfirmed = 'test@example.com';
		UserModel::save($user);

		$this->assert->doesNotThrow(function()
		{
			Auth::login('existing', 'ble', false);
		});
	}



	protected function prepareValidUser()
	{
		$user = UserModel::spawn();
		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$user->setName('existing');
		$user->passHash = UserModel::hashPassword('ble', $user->passSalt);
		return $user;
	}
}
