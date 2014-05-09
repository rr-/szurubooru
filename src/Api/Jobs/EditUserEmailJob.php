<?php
class EditUserEmailJob extends AbstractUserJob
{
	const NEW_EMAIL = 'new-email';

	public function isSatisfied()
	{
		return $this->hasArgument(self::NEW_EMAIL);
	}

	public function execute()
	{
		if (getConfig()->registration->needEmailForRegistering)
			if (!$this->hasArgument(self::NEW_EMAIL) or empty($this->getArgument(self::NEW_EMAIL)))
				throw new SimpleException('E-mail address is required - you will be sent confirmation e-mail.');

		$user = $this->user;
		$newEmail = $this->getArgument(self::NEW_EMAIL);

		$oldEmail = $user->getConfirmedEmail();
		if ($oldEmail == $newEmail)
			return $user;

		$user->setUnconfirmedEmail($newEmail);
		$user->setConfirmedEmail(null);

		if ($this->getContext() == self::CONTEXT_NORMAL)
		{
			UserModel::save($user);
			self::observeSave($user);
		}

		Logger::log('{user} changed {subject}\'s e-mail to {mail}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user),
			'mail' => $newEmail]);

		return $user;
	}

	public static function observeSave($user)
	{
		if (Access::check(new Privilege(Privilege::ChangeUserEmailNoConfirm), $user))
		{
			$user->confirmEmail();
		}
		else
		{
			if (!empty($user->getUnconfirmedEmail()))
				ActivateUserEmailJob::sendEmail($user);
		}
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::RegisterAccount
				: Privilege::ChangeUserEmail,
			Access::getIdentity($this->user));
	}
}
