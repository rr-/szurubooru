<?php
class EditUserJob extends AbstractUserJob
{
	public function execute()
	{
		$user = $this->user;

		LogHelper::bufferChanges();

		$subJobs =
		[
			new EditUserNameJob(),
			new EditUserPasswordJob(),
			new EditUserEmailJob(),
			new EditUserAccessRankJob(),
		];

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
}
