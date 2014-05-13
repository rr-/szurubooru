<?php
class PasswordResetJobTest extends AbstractTest
{
	public function testDontSendIfUnconfirmedMail()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->throws(function() use ($user)
		{
			Api::run(
				new PasswordResetJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		}, 'no e-mail confirmed');
	}

	public function testSending()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setConfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual(0, Mailer::getMailCounter());

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new PasswordResetJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$this->assert->areEqual(1, Mailer::getMailCounter());

		$tokens = Mailer::getMailsSent()[0]->tokens;
		$tokenText = $tokens['token'];
		$token = TokenModel::getByToken($tokenText);

		$this->assert->areEqual($user->getId(), $token->getUser()->getId());
		$this->assert->isTrue(strpos($tokens['link'], $tokenText) !== false);

		return $tokenText;
	}

	public function testObtainingNewPassword()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setConfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new PasswordResetJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$tokenText = Mailer::getMailsSent()[0]->tokens['token'];

		$ret = $this->assert->doesNotThrow(function() use ($tokenText)
		{
			return Api::run(
				new PasswordResetJob(),
				[
					JobArgs::ARG_TOKEN => $tokenText,
				]);
		});

		$user = $ret->user;
		$newPassword = $ret->newPassword;
		$newPasswordHash = UserModel::hashPassword($newPassword, $user->getPasswordSalt());

		$this->assert->areEqual($newPasswordHash, $user->getPasswordHash());
		$this->assert->doesNotThrow(function() use ($user, $newPassword)
		{
			Auth::login($user->getName(), $newPassword, false);
		});
	}

	public function testUsingTokenTwice()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setConfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		Api::run(
			new PasswordResetJob(),
			[
				JobArgs::ARG_USER_NAME => $user->getName(),
			]);

		$tokenText = Mailer::getMailsSent()[0]->tokens['token'];

		Api::run(
			new PasswordResetJob(),
			[
				JobArgs::ARG_TOKEN => $tokenText,
			]);

		$this->assert->throws(function() use ($tokenText)
		{
			Api::run(
				new PasswordResetJob(),
				[
					JobArgs::ARG_TOKEN => $tokenText,
				]);
		}, 'This token was already used');
	}
}
