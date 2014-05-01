<?php
class Api
{
	public static function run($job, $jobArgs)
	{
		$user = Auth::getCurrentUser();

		return \Chibi\Database::transaction(function() use ($job, $jobArgs)
		{
			if ($job->requiresAuthentication())
				Access::assertAuthentication();

			if ($job->requiresConfirmedEmail())
				Access::assertEmailConfirmation();

			return $job->execute($jobArgs);
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
