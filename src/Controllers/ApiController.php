<?php
class ApiController extends AbstractController
{
	public function runAction()
	{
		$this->interceptErrors(function()
		{
			$context = Core::getContext();

			if (!Auth::isLoggedIn())
			{
				$auth = InputHelper::get('auth');
				if ($auth)
				{
					Auth::login($auth['user'], $auth['pass'], false);
				}
			}

			$jobName = InputHelper::get('name');
			$jobArgs = InputHelper::get('args');

			$job = $this->jobFromName($jobName);
			if (!$job)
				throw new SimpleException('Unknown job: ' . $jobName);
			if (!$job->isAvailableToPublic())
				throw new SimpleException('This job is unavailable for public.');

			if (isset($_FILES['args']))
			{
				foreach (array_keys($_FILES['args']['name']) as $key)
				{
					$jobArgs[$key] = new ApiFileInput(
						$_FILES['args']['tmp_name'][$key],
						$_FILES['args']['name'][$key]);
				}
			}

			$context->transport->status = Api::run($job, $jobArgs);
		});

		$this->renderAjax();
	}


	private function jobFromName($jobName)
	{
		$jobClassNames = Api::getAllJobClassNames();
		foreach ($jobClassNames as $className)
		{
			$job = (new ReflectionClass($className))->newInstance();
			if ($job->getName() == $jobName)
				return $job;
			$job = null;
		}
		return null;
	}
}
