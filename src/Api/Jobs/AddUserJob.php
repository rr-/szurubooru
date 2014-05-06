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

		if ($firstUser)
		{
			$user->setAccessRank(new AccessRank(AccessRank::Admin));
		}
		else
		{
			$user->setAccessRank(new AccessRank(AccessRank::Registered));
		}

		$arguments = $this->getArguments();
		$arguments[EditUserJob::USER_ENTITY] = $user;

		Logger::bufferChanges();
		try
		{
			$job = new EditUserJob();
			$job->setContext(self::CONTEXT_BATCH_ADD);
			Api::run($job, $arguments);
		}
		finally
		{
			Logger::discardBuffer();
		}

		//save the user to db if everything went okay
		UserModel::save($user);
		EditUserEmailJob::observeSave($user);

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
