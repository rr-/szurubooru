<?php
class AddUserJob extends AbstractJob
{
	public function __construct()
	{
		$this->addSubJob(new EditUserAccessRankJob());
		$this->addSubJob(new EditUserNameJob());
		$this->addSubJob(new EditUserPasswordJob());
		$this->addSubJob(new EditUserEmailJob());
	}

	public function execute()
	{
		$firstUser = UserModel::getCount() == 0;

		$user = UserModel::spawn();
		$user->setJoinTime(time());
		$user->setStaffConfirmed($firstUser);
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
		$arguments[JobArgs::ARG_USER_ENTITY] = $user;

		Logger::bufferChanges();
		foreach ($this->getSubJobs() as $subJob)
		{
			$subJob->setContext(self::CONTEXT_BATCH_ADD);

			try
			{
				Api::run($subJob, $arguments);
			}
			catch (ApiJobUnsatisfiedException $e)
			{
			}
			finally
			{
				Logger::discardBuffer();
			}
		}

		//save the user to db if everything went okay
		UserModel::save($user);
		EditUserEmailJob::observeSave($user);

		Logger::log('{subject} just signed up', [
			'subject' => TextHelper::reprUser($user)]);

		Logger::flush();

		return $user;
	}

	public function getRequiredArguments()
	{
		return null;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::RegisterAccount);
	}
}
