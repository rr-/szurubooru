<?php
abstract class AbstractFullApiTest extends AbstractTest
{
	protected $testedJobs = [];

	public function teardown()
	{
		$testedJobs = array_map(function($job)
		{
			return get_class($job);
		}, $this->testedJobs);
		$allJobs = $this->getAllJobs();
		foreach ($allJobs as $x)
		{
			if (!in_array($x, $testedJobs))
				$this->assert->fail($x . ' appears to be untested');
		}
	}

	protected function getAllJobs()
	{
		$files = glob(getConfig()->rootDir . DS . 'src' . DS . 'Api' . DS . 'Jobs' . DS . '*.php');
		\Chibi\Util\Reflection::loadClasses($files);
		return array_filter(get_declared_classes(), function($x)
		{
			$class = new ReflectionClass($x);
			return !$class->isAbstract() and $class->isSubClassOf('AbstractJob');
		});
	}
}
