<?php
class AddUserJob extends AbstractJob
{
	public function execute()
	{
		$firstUser = UserModel::getCount() == 0;

		$user = UserModel::spawn();
		$user->joinDate = time();
		$user->staffConfirmed = $firstUser;
		$user->setName($this->getArgument(EditUserNameJob::NEW_USER_NAME));
		UserModel::forgeId($user);

		$arguments = $this->getArguments();
		$arguments[EditUserJob::USER_ENTITY] = $user;

		$arguments[EditUserAccessRankJob::NEW_ACCESS_RANK] = $firstUser
			? AccessRank::Admin
			: AccessRank::Registered;

		Logger::bufferChanges();
		Api::disablePrivilegeChecking();
		Api::run((new EditUserJob)->skipSaving(), $arguments);
		Api::enablePrivilegeChecking();
		Logger::setBuffer([]);

		if ($firstUser)
			$user->confirmEmail();

		//save the user to db if everything went okay
		UserModel::save($user);

		Logger::log('{subject} just signed up', [
			'subject' => TextHelper::reprUser($user)]);

		Logger::flush();

		return $user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::RegisterAccount);
	}
}
