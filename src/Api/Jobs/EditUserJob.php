<?php
class EditUserJob extends AbstractUserEditJob
{
	protected $subJobs;

	public function __construct()
	{
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
			catch (SimpleException $e)
			{
			}
		}
		return false;
	}

	public function execute()
	{
		$user = $this->user;

		Logger::bufferChanges();

		foreach ($this->subJobs as $subJob)
		{
			if ($this->skipSaving)
				$subJob->skipSaving();

			$args = $this->getArguments();
			$args[self::USER_ENTITY] = $user;
			try
			{
				Api::run($subJob, $args);
			}
			catch (ApiMissingArgumentException $e)
			{
			}
		}

		if (!$this->skipSaving)
			UserModel::save($user);

		Logger::flush();
		return $user;
	}

	public function requiresPrivilege()
	{
		return false;
	}
}
