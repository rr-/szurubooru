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
		$allJobs = Api::getAllJobClassNames();
		foreach ($allJobs as $x)
		{
			if (!in_array($x, $testedJobs))
				$this->assert->fail($x . ' appears to be untested');
		}
	}
}
