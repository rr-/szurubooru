<?php
class AcceptUserRegistrationJob extends AbstractUserJob
{
	public function execute()
	{
		$user = $this->user;

		$user->setStaffConfirmed(true);
		UserModel::save($user);

		Logger::log('{user} confirmed {subject}\'s account', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::AcceptUserRegistration);
	}
}
