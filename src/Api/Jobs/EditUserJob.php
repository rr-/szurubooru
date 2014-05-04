<?php
class EditUserJob extends AbstractUserJob
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

		LogHelper::bufferChanges();

		foreach ($subJobs as $subJob)
		{
			$args = $this->getArguments();
			$args[self::USER_NAME] = $user->name;
			try
			{
				Api::run($subJob, $args);
			}
			catch (ApiMissingArgumentException $e)
			{
			}
		}

		LogHelper::flush();
		return $user;
	}

	public function requiresPrivilege()
	{
		return false;
	}
}
