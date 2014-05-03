<?php
class Api
{
	public static function run($job, $jobArgs)
	{
		$user = Auth::getCurrentUser();

		return \Chibi\Database::transaction(function() use ($job, $jobArgs)
		{
			$job->setArguments($jobArgs);
			$job->prepare();

			if ($job->requiresAuthentication())
				Access::assertAuthentication();

			if ($job->requiresConfirmedEmail())
				Access::assertEmailConfirmation();

			$p = $job->requiresPrivilege();
			list ($privilege, $subPrivilege) = is_array($p)
				? $p
				: [$p, false];
			if ($privilege !== false)
				Access::assert($privilege, $subPrivilege);

			return $job->execute();
		});
	}

	public static function runMultiple($jobs)
	{
		$statuses = [];
		\Chibi\Database::transaction(function() use ($jobs, &$statuses)
		{
			foreach ($jobs as $jobItem)
			{
				list ($job, $jobArgs) = $jobItem;
				$statuses []= self::run($job, $jobArgs);
			}
		});
		return $statuses;
	}
}
