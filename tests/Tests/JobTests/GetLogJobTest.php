<?php
class GetLogJobTest extends AbstractTest
{
	public function testRetrieving()
	{
		$ret = $this->run([]);

		$this->assert->areEqual(2, count($ret->entities));
		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);
		$this->assert->isTrue(strpos($ret->entities[0], 'line3') !== false);
		$this->assert->isTrue(strpos($ret->entities[1], 'line2') !== false);
	}

	public function testRetrievingWithPaging()
	{
		$ret = $this->run([JobArgs::ARG_PAGE_NUMBER => 2]);

		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(2, $ret->page);
		$this->assert->isTrue(strpos($ret->entities[0], 'line1') !== false);
	}

	public function testRetrievingWithFilter()
	{
		$ret = $this->run([JobArgs::ARG_QUERY => 'line2']);

		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(1, $ret->entityCount);
		$this->assert->areEqual(1, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);
		$this->assert->isTrue(strpos($ret->entities[0], 'line2') !== false);
	}

	private function run($args)
	{
		Core::getConfig()->browsing->logsPerPage = 2;
		$this->grantAccess('viewLog');
		Logger::log('line1');
		Logger::log('line2');
		Logger::log('line3');

		$logId = Logger::getLogPath();
		$logId = basename($logId);
		$args[JobArgs::ARG_LOG_ID] = $logId;

		return $this->assert->doesNotThrow(function() use ($args)
		{
			return Api::run(
				new GetLogJob(),
				$args);
		});
	}
}
