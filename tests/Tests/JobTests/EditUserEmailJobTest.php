<?php
class EditUserEmailJobTest extends AbstractTest
{
	public function testNoConfirmation()
	{
		getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		getConfig()->privileges->changeUserEmailNoConfirm = 'anonymous';
		$this->grantAccess('changeUserEmail');

		$user = $this->userMocker->mockSingle();

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserEmailJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_EMAIL => 'xena@other-side.gr',
				]);
		});

		$this->assert->areEqual(null, $user->getUnconfirmedEmail());
		$this->assert->areEqual('xena@other-side.gr', $user->getConfirmedEmail());

		$this->assert->areEqual(0, Mailer::getMailCounter());
	}

	public function testConfirmation()
	{
		getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		getConfig()->privileges->changeUserEmailNoConfirm = 'admin';
		$this->grantAccess('changeUserEmail');

		$user = $this->userMocker->mockSingle();

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserEmailJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_EMAIL => 'xena@other-side.gr',
				]);
		});

		$this->assert->areEqual('xena@other-side.gr', $user->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user->getConfirmedEmail());

		$this->assert->areEqual(1, Mailer::getMailCounter());
	}

	public function testInvalidEmail()
	{
		getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();

		getConfig()->privileges->changeUserEmailNoConfirm = 'nobody';
		$this->grantAccess('changeUserEmail');

		$user = $this->userMocker->mockSingle();

		$this->assert->throws(function() use ($user)
		{
			Api::run(
				new EditUserEmailJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_EMAIL => 'hrmfbpdvpds@brtedf',
				]);
		}, 'E-mail address appears to be invalid');
	}

	public function testChangingToExistingDenial()
	{
		getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		getConfig()->privileges->changeUserEmailNoConfirm = 'anonymous';
		$this->grantAccess('changeUserEmail');

		list ($user, $otherUser)
			= $this->userMocker->mockMultiple(2);
		$otherUser->setUnconfirmedEmail('super@mario.plumbing');
		UserModel::save($otherUser);

		$this->assert->throws(function() use ($user, $otherUser)
		{
			Api::run(
				new EditUserEmailJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_EMAIL => $otherUser->getUnconfirmedEmail(),
				]);
		}, 'User with this e-mail is already registered');

		$this->assert->areEqual(null, $user->getUnconfirmedEmail());
		$this->assert->areEqual(null, $user->getConfirmedEmail());

		$this->assert->areEqual(0, Mailer::getMailCounter());
	}
}
