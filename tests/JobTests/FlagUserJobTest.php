<?php
class FlagUserJobTest extends AbstractTest
{
	public function testFlagging()
	{
		$this->grantAccess('flagUser');
		$user = $this->mockUser();

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new FlagUserJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$logPath = Logger::getLogPath();
		$logs = file_get_contents($logPath);
		$logs = array_filter(explode("\n", $logs));
		$this->assert->areEqual(1, count($logs));
		$this->assert->isTrue(strpos($logs[0], 'flagged +' . $user->getName() . ' for moderator attention') !== false);
	}

	public function testDoubleFlagging()
	{
		$this->grantAccess('flagUser');
		$user = $this->mockUser();

		$this->assert->doesNotThrow(function() use ($user)
		{
			Api::run(
				new FlagUserJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$this->assert->throws(function() use ($user)
		{
			return Api::run(
				new FlagUserJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		}, 'You already flagged this user');
	}
}
