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

		$this->runSubJobs($this->getSubJobs(), $arguments);
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

	public function getRequiredMainPrivilege()
	{
		return Privilege::RegisterAccount;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}

	private function runSubJobs($subJobs, $arguments)
	{
		foreach ($subJobs as $subJob)
		{
			Logger::bufferChanges();
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
	}
}
