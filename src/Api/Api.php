<?php
final class Api
{
	public static function run($job, $jobArgs)
	{
		$user = Auth::getCurrentUser();

		return \Chibi\Database::transaction(function() use ($job, $jobArgs)
		{
			$job->setArguments($jobArgs);

			self::checkArguments($job);

			$job->prepare();

			self::checkPrivileges($job);

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

	public static function checkArguments(AbstractJob $job)
	{
		self::runArgumentCheck($job, $job->getRequiredArguments());
	}

	public static function checkPrivileges(AbstractJob $job)
	{
		if ($job->isAuthenticationRequired())
			Access::assertAuthentication();

		if ($job->isConfirmedEmailRequired())
			Access::assertEmailConfirmation();

		$privileges = $job->getRequiredPrivileges();
		if ($privileges !== false)
		{
			if (!is_array($privileges))
				$privileges = [$privileges];

			foreach ($privileges as $privilege)
				Access::assert($privilege);
		}
	}

	private static function runArgumentCheck($job, $item)
	{
		if (is_array($item))
			throw new Exception('Argument definition cannot be an array.');
		elseif ($item instanceof JobArgsNestedStruct)
		{
			if ($item instanceof JobArgsAlternative)
			{
				$success = false;
				foreach ($item->args as $subItem)
				{
					try
					{
						self::runArgumentCheck($job, $subItem);
						$success = true;
					}
					catch (ApiJobUnsatisfiedException $e)
					{
					}
				}
				if (!$success)
					throw new ApiJobUnsatisfiedException($job);
			}
			elseif ($item instanceof JobArgsConjunction)
			{
				foreach ($item->args as $subItem)
					!self::runArgumentCheck($job, $subItem);
			}
		}
		elseif ($item === null)
			return;
		elseif (!$job->hasArgument($item))
			throw new ApiJobUnsatisfiedException($job, $item);
	}
}
