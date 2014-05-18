<?php
class EditUserEmailJobTest extends AbstractTest
{
	public function testNoConfirmation()
	{
		Core::getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		Core::getConfig()->privileges->editUserEmailNoConfirm = 'anonymous';
		$this->grantAccess('editUserEmail');

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

		$this->assert->isNull($user->getUnconfirmedEmail());
		$this->assert->areEqual('xena@other-side.gr', $user->getConfirmedEmail());

		$this->assert->areEqual(0, Mailer::getMailCounter());
	}

	public function testConfirmation()
	{
		Core::getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		Core::getConfig()->privileges->editUserEmailNoConfirm = 'admin';
		$this->grantAccess('editUserEmail');

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
		$this->assert->isNull($user->getConfirmedEmail());

		$this->assert->areEqual(1, Mailer::getMailCounter());
	}

	public function testInvalidEmail()
	{
		Core::getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();

		Core::getConfig()->privileges->editUserEmailNoConfirm = 'nobody';
		$this->grantAccess('editUserEmail');

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
		Core::getConfig()->registration->needEmailForRegistering = false;
		Mailer::mockSending();
		$this->assert->areEqual(0, Mailer::getMailCounter());

		Core::getConfig()->privileges->editUserEmailNoConfirm = 'anonymous';
		$this->grantAccess('editUserEmail');

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

		$this->assert->isNull($user->getUnconfirmedEmail());
		$this->assert->isNull($user->getConfirmedEmail());

		$this->assert->areEqual(0, Mailer::getMailCounter());
	}
}
