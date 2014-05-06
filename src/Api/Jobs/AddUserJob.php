<?php
class AddUserJob extends AbstractJob
{
	public function execute()
	{
		$firstUser = UserModel::getCount() == 0;

		$user = UserModel::spawn();
		$user->joinDate = time();
		$user->staffConfirmed = $firstUser;
		UserModel::forgeId($user);

		$arguments = $this->getArguments();
		$arguments[EditUserJob::USER_ENTITY] = $user;

		Logger::bufferChanges();
		$job = new EditUserJob();
		$job->setContext(self::CONTEXT_BATCH_ADD);
		Api::run($job, $arguments);
		Logger::setBuffer([]);

		if ($firstUser)
		{
			$user->setAccessRank(new AccessRank(AccessRank::Admin));
			$user->confirmEmail();
		}
		else
		{
			$user->setAccessRank(new AccessRank(AccessRank::Registered));
		}

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
