<?php
class ToggleUserBanJob extends AbstractUserJob
{
	public function execute()
	{
		$user = $this->user;
		$banned = boolval($this->getArgument(self::STATE));

		if ($banned)
			$user->ban();
		else
			$user->unban();
		UserModel::save($user);

		Logger::log(
			$banned
				? '{user} banned {subject}'
				: '{user} unbanned {subject}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::BanUser,
			Access::getIdentity($this->user));
	}
}
