<?php
class ActivateUserEmailJobTest extends AbstractTest
{
	public function testSending()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual(0, Mailer::getMailCounter());

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new ActivateUserEmailJob(),
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

	public function testConfirming()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual('godzilla@whitestar.gov', $user->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user->getConfirmedEmail());

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$tokenText = Mailer::getMailsSent()[0]->tokens['token'];

		$this->assert->doesNotThrow(function() use ($tokenText)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					JobArgs::ARG_TOKEN => $tokenText,
				]);
		});

		//reload local entity after changes done by the job
		$user = UserModel::getById($user->getId());

		$this->assert->areEqual(null, $user->getUnconfirmedEmail());
		$this->assert->areEqual('godzilla@whitestar.gov', $user->getConfirmedEmail());
	}

	public function testUsingTokenTwice()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->userMocker->mockSingle();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual('godzilla@whitestar.gov', $user->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user->getConfirmedEmail());

		Api::run(
			new ActivateUserEmailJob(),
			[
				JobArgs::ARG_USER_NAME => $user->getName(),
			]);

		$tokenText = Mailer::getMailsSent()[0]->tokens['token'];

		Api::run(
			new ActivateUserEmailJob(),
			[
				JobArgs::ARG_TOKEN => $tokenText,
			]);

		$this->assert->throws(function() use ($tokenText)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					JobArgs::ARG_TOKEN => $tokenText,
				]);
		}, 'This token was already used');
	}
}
