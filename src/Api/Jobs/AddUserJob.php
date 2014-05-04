<?php
class AddUserJob extends AbstractJob
{
	public function execute()
	{
		$firstUser = UserModel::getCount() == 0;

		$user = UserModel::spawn();
		$user->joinDate = time();
		$user->staffConfirmed = $firstUser;
		$user->name = $this->getArgument(EditUserNameJob::NEW_USER_NAME);
		UserModel::forgeId($user);

		$arguments = $this->getArguments();
		$arguments[EditUserJob::USER_NAME] = $user->name;

		$arguments[EditUserAccessRankJob::NEW_ACCESS_RANK] = $firstUser
			? AccessRank::Admin
			: AccessRank::Registered;

		LogHelper::bufferChanges();
		Api::disablePrivilegeChecking();
		Api::run(new EditUserJob(), $arguments);
		Api::enablePrivilegeChecking();
		LogHelper::setBuffer([]);

		if ($firstUser)
			$user->confirmEmail();

		//load the user after edits
		$user = UserModel::findById($user->id);

		//save the user to db if everything went okay
		UserModel::save($user);

		LogHelper::log('{subject} just signed up', [
			'subject' => TextHelper::reprUser($user)]);

		LogHelper::flush();

		return $user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::RegisterAccount);
	}
}
