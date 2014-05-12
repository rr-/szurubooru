<?php
class EditUserJob extends AbstractJob
{
	protected $userRetriever;
	protected $subJobs;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
		$this->subJobs =
		[
			new EditUserAccessRankJob(),
			new EditUserNameJob(),
			new EditUserPasswordJob(),
			new EditUserEmailJob(),
		];
	}

	public function canEditAnything($user)
	{
		$this->privileges = [];
		foreach ($this->subJobs as $subJob)
		{
			try
			{
				$subJob->user = $user;
				Api::checkPrivileges($subJob);
				return true;
			}
			catch (AccessException $e)
			{
			}
		}
		return false;
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();

		Logger::bufferChanges();

		foreach ($this->subJobs as $subJob)
		{
			$subJob->setContext($this->getContext() == self::CONTEXT_BATCH_ADD
				? self::CONTEXT_BATCH_ADD
				: self::CONTEXT_BATCH_EDIT);

			$args = $this->getArguments();
			$args[JobArgs::ARG_USER_ENTITY] = $user;
			try
			{
				Api::run($subJob, $args);
			}
			catch (ApiJobUnsatisfiedException $e)
			{
			}
		}

		if ($this->getContext() == self::CONTEXT_NORMAL)
		{
			UserModel::save($user);
			EditUserEmailJob::observeSave($user);
			Logger::flush();
		}

		return $user;
	}

	public function getRequiredArguments()
	{
		return $this->userRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return false;
	}
}
