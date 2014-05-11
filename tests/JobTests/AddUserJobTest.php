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
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
				]);
		});

		//first user = admin
		$this->assert->areEqual('dummy', $user1->getName());
		$this->assert->areEquivalent(new AccessRank(AccessRank::Admin), $user1->getAccessRank());
		$this->assert->isFalse(empty($user1->getPasswordSalt()));
		$this->assert->isFalse(empty($user1->getPasswordHash()));

		$user2 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy2',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
				]);
		});

		//any other user = non-admin
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
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => str_repeat('s', getConfig()->registration->passMinLength - 1),
				]);
		}, 'Password must have at least');
	}

	public function testSkippingMailingUponFailing() //yo dog
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$this->assert->areEqual(0, Mailer::getMailCounter());

		$this->grantAccess('registerAccount');
		$this->assert->throws(function()
		{
			Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => str_repeat('s', getConfig()->registration->passMinLength - 1),
					JobArgs::ARG_NEW_EMAIL => 'godzilla@whitestar.gov',
				]);
		}, 'Password must have at least');

		$this->assert->areEqual(0, Mailer::getMailCounter());
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
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => $pass,
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
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
				]);
		});

		$this->assert->throws(function()
		{
			Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
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
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_ACCESS_RANK => 'power-user',
				]);
		}, 'Insufficient privileges');
	}

	public function testEmailsMixedConfirmation()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		getConfig()->privileges->changeUserEmailNoConfirm = 'admin';
		$this->grantAccess('registerAccount');

		$user1 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla@whitestar.gov',
				]);
		});

		//first user = admin = has confirmed e-mail automatically
		$this->assert->areEqual(null, $user1->getUnconfirmedEmail());
		$this->assert->areEqual('godzilla@whitestar.gov', $user1->getConfirmedEmail());

		$user2 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy2',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla2@whitestar.gov',
				]);
		});

		//any other user = non-admin = has to confirmed e-mail manually
		$this->assert->areEqual('godzilla2@whitestar.gov', $user2->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user2->getConfirmedEmail());

		$this->assert->areEqual(1, Mailer::getMailCounter());
	}

	public function testEmailsEveryoneMustConfirm()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		getConfig()->privileges->changeUserEmailNoConfirm = 'nobody';
		$this->grantAccess('registerAccount');

		$user1 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla@whitestar.gov',
				]);
		});

		$this->assert->areEqual('godzilla@whitestar.gov', $user1->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user1->getConfirmedEmail());

		$user2 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy2',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla2@whitestar.gov',
				]);
		});

		$this->assert->areEqual('godzilla2@whitestar.gov', $user2->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user2->getConfirmedEmail());

		$this->assert->areEqual(2, Mailer::getMailCounter());
	}

	public function testEmailsEveryoneSkipConfirm()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		getConfig()->privileges->changeUserEmailNoConfirm = 'anonymous';
		$this->grantAccess('registerAccount');

		$user1 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla@whitestar.gov',
				]);
		});

		$this->assert->areEqual(null, $user1->getUnconfirmedEmail());
		$this->assert->areEqual('godzilla@whitestar.gov', $user1->getConfirmedEmail());

		$user2 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy2',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla2@whitestar.gov',
				]);
		});

		$this->assert->areEqual(null, $user2->getUnconfirmedEmail());
		$this->assert->areEqual('godzilla2@whitestar.gov', $user2->getConfirmedEmail());

		$this->assert->areEqual(0, Mailer::getMailCounter());
	}

	public function testEmailsTwoUsersSameMail()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		$this->grantAccess('registerAccount');

		$user1 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla@whitestar.gov',
				]);
		});

		$user2 = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddUserJob(),
				[
					JobArgs::ARG_NEW_USER_NAME => 'dummy2',
					JobArgs::ARG_NEW_PASSWORD => 'sekai',
					JobArgs::ARG_NEW_EMAIL => 'godzilla@whitestar.gov',
				]);
		});

		$this->assert->areEqual(2, Mailer::getMailCounter());
		$token1text = Mailer::getMailsSent()[0]->tokens['token'];
		$token2text = Mailer::getMailsSent()[1]->tokens['token'];
		$this->assert->areNotEqual($token1text, $token2text);

		$token1 = TokenModel::getByToken($token1text);
		$token2 = TokenModel::getByToken($token2text);

		$this->assert->areEqual($user1->getId(), $token1->getUser()->getId());
		$this->assert->areEqual($user2->getId(), $token2->getUser()->getId());
	}

	public function testLogBuffering()
	{
		$this->testSaving();

		$logPath = Logger::getLogPath();
		$x = file_get_contents($logPath);
		$lines = array_filter(explode("\n", $x));
		$this->assert->areEqual(2, count($lines));
	}
}
