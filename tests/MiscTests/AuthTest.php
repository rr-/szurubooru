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
			Auth::login('existing', 'bleee', false);
		});

		$this->assert->isTrue(Auth::isLoggedIn());
	}

	public function testLoginViaEmail()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		$user->setConfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->doesNotThrow(function() use ($user)
		{
			Auth::login($user->getConfirmedEmail(), 'bleee', false);
		});

		$this->assert->isTrue(Auth::isLoggedIn());
	}

	public function testLogout()
	{
		$this->assert->isFalse(Auth::isLoggedIn());
		$this->testValidPassword();
		$this->assert->isTrue(Auth::isLoggedIn());
		Auth::setCurrentUser(null);
		$this->assert->isFalse(Auth::isLoggedIn());
	}

	public function testInvalidUserName()
	{
		$this->assert->throws(function()
		{
			Auth::login('non-existing', 'wrong-password', false);
		}, 'invalid user name');
	}

	public function testInvalidPassword()
	{
		$user = $this->prepareValidUser();
		$user->setPassword('blee2');
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
			Auth::login('existing', 'bleee', false);
		}, 'You are banned');
	}

	public function testStaffConfirmationEnabled()
	{
		getConfig()->registration->staffActivation = true;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		$user->setStaffConfirmed(false);
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'bleee', false);
		}, 'staff hasn\'t confirmed');
	}

	public function testStaffConfirmationDisabled()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = false;

		$user = $this->prepareValidUser();
		$user->setStaffConfirmed(false);
		UserModel::save($user);

		$this->assert->doesNotThrow(function()
		{
			Auth::login('existing', 'bleee', false);
		});
	}

	public function testMailConfirmationEnabledFail1()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = true;

		$user = $this->prepareValidUser();
		$user->setStaffConfirmed(false);
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'bleee', false);
		}, 'need e-mail address confirmation');
	}

	public function testMailConfirmationEnabledFail2()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = true;

		$user = $this->prepareValidUser();
		$user->setStaffConfirmed(false);
		$user->setUnconfirmedEmail('test@example.com');
		UserModel::save($user);

		$this->assert->throws(function()
		{
			Auth::login('existing', 'bleee', false);
		}, 'need e-mail address confirmation');
	}

	public function testMailConfirmationEnabledPass()
	{
		getConfig()->registration->staffActivation = false;
		getConfig()->registration->needEmailForRegistering = true;

		$user = $this->prepareValidUser();
		$user->setStaffConfirmed(false);
		$user->setConfirmedEmail('test@example.com');
		UserModel::save($user);

		$this->assert->doesNotThrow(function()
		{
			Auth::login('existing', 'bleee', false);
		});
	}



	protected function prepareValidUser()
	{
		$user = UserModel::spawn();
		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$user->setName('existing');
		$user->setPassword('bleee');
		return $user;
	}
}
