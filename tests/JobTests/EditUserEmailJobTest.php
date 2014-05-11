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

		$user = $this->mockUser();

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

		$user = $this->mockUser();

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

		$user = $this->mockUser();

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
}
