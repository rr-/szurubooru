<?php
class ActivateUserEmailJobTest extends AbstractTest
{
	public function testSending()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user = $this->mockUser();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual(0, Mailer::getMailCounter());

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					ActivateUserEmailJob::USER_NAME => $user->getName(),
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

		$user = $this->mockUser();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual('godzilla@whitestar.gov', $user->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user->getConfirmedEmail());

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					ActivateUserEmailJob::USER_NAME => $user->getName(),
				]);
		});

		$tokenText = Mailer::getMailsSent()[0]->tokens['token'];

		$this->assert->doesNotThrow(function() use ($tokenText)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					ActivateUserEmailJob::TOKEN => $tokenText,
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

		$user = $this->mockUser();
		$user->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user);

		$this->assert->areEqual('godzilla@whitestar.gov', $user->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user->getConfirmedEmail());

		Api::run(
			new ActivateUserEmailJob(),
			[
				ActivateUserEmailJob::USER_NAME => $user->getName(),
			]);

		$tokenText = Mailer::getMailsSent()[0]->tokens['token'];

		Api::run(
			new ActivateUserEmailJob(),
			[
				ActivateUserEmailJob::TOKEN => $tokenText,
			]);

		$this->assert->throws(function() use ($tokenText)
		{
			Api::run(
				new ActivateUserEmailJob(),
				[
					ActivateUserEmailJob::TOKEN => $tokenText,
				]);
		}, 'This token was already used');
	}

	public function testTokensTwoUsersSameMail()
	{
		getConfig()->registration->needEmailForRegistering = true;
		Mailer::mockSending();

		$user1 = $this->mockUser();
		$user2 = $this->mockUser();
		$user1->setUnconfirmedEmail('godzilla@whitestar.gov');
		$user2->setUnconfirmedEmail('godzilla@whitestar.gov');
		UserModel::save($user1);
		UserModel::save($user2);

		Api::run(
			new ActivateUserEmailJob(),
			[
				ActivateUserEmailJob::USER_NAME => $user1->getName(),
			]);

		Api::run(
			new ActivateUserEmailJob(),
			[
				ActivateUserEmailJob::USER_NAME => $user2->getName(),
			]);

		$tokens1 = Mailer::getMailsSent()[0]->tokens;
		$tokens2 = Mailer::getMailsSent()[1]->tokens;
		$token1text = $tokens1['token'];
		$token2text = $tokens2['token'];
		$this->assert->areNotEqual($token1text, $token2text);

		$token1 = TokenModel::getByToken($token1text);
		$token2 = TokenModel::getByToken($token2text);

		$this->assert->areEqual($user1->getId(), $token1->getUser()->getId());
		$this->assert->areEqual($user2->getId(), $token2->getUser()->getId());
		$this->assert->areNotEqual($token1->getUserId(), $token2->getUserId());
	}
}
