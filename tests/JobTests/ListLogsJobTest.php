<?php
class ListLogsJobTest extends AbstractTest
{
	public function testListing()
	{
		$this->grantAccess('listLogs');

		getConfig()->main->logsPath = dirname(__DIR__) . '/logs/test1.log';
		Logger::init();

		Logger::log('nonsense');

		getConfig()->main->logsPath = dirname(__DIR__) . '/logs/test2.log';
		Logger::init();

		Logger::log('nonsense');

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListLogsJob(), []);
		});

		$this->assert->areEqual(2, count($ret));
		$this->assert->areEqual('test2.log', $ret[0]);
		$this->assert->areEqual('test1.log', $ret[1]);
	}
}
