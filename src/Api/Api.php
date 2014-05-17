<?php
final class Api
{
	public static function run(IJob $job, $jobArgs)
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

	public static function checkArguments(IJob $job)
	{
		self::runArgumentCheck($job, $job->getRequiredArguments());
	}

	public static function checkPrivileges(IJob $job)
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

	private static function runArgumentCheck(IJob $job, $item)
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

	public static function getAllJobClassNames()
	{
		$pathToJobs = Core::getConfig()->rootDir . DS . 'src' . DS . 'Api' . DS . 'Jobs';
		$directory = new RecursiveDirectoryIterator($pathToJobs);
		$iterator = new RecursiveIteratorIterator($directory);
		$regex = new RegexIterator($iterator, '/^.+Job\.php$/i');
		$files = array_keys(iterator_to_array($regex));

		\Chibi\Util\Reflection::loadClasses($files);
		return array_filter(get_declared_classes(), function($x)
		{
			$class = new ReflectionClass($x);
			return !$class->isAbstract() and $class->isSubClassOf('AbstractJob');
		});
	}
}
