<?php
class EditUserEmailJob extends AbstractUserJob
{
	const NEW_EMAIL = 'new-email';

	public function execute()
	{
		if (getConfig()->registration->needEmailForRegistering)
			if (!$this->hasArguemnt(self::NEW_EMAIL) or empty($this->getArgument(self::NEW_EMAIL)))
				throw new SimpleException('E-mail address is required - you will be sent confirmation e-mail.');

		$user = $this->user;
		$newEmail = UserModel::validateEmail($this->getArgument(self::NEW_EMAIL));

		$oldEmail = $user->emailConfirmed;
		if ($oldEmail == $newEmail)
			return $user;

		$user->emailUnconfirmed = $newEmail;
		$user->emailConfirmed = null;

		if (Auth::getCurrentUser()->id == $user->id)
		{
			if (!empty($newEmail))
				ActivateUserEmailJob::sendEmail($user);
		}
		else
		{
			$user->confirmEmail();
		}

		UserModel::save($user);

		LogHelper::log('{user} changed {subject}\'s e-mail to {mail}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user),
			'mail' => $newEmail]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::ChangeUserAccessRank,
			Access::getIdentity($this->user));
	}
}
