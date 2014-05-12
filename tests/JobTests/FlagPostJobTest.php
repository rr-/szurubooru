<?php
class FlagPostJobTest extends AbstractTest
{
	public function testFlagging()
	{
		$this->grantAccess('flagPost');
		$post = $this->mockPost($this->mockUser());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new FlagPostJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		});

		$logPath = Logger::getLogPath();
		$logs = file_get_contents($logPath);
		$logs = array_filter(explode("\n", $logs));
		$this->assert->areEqual(1, count($logs));
		$this->assert->isTrue(strpos($logs[0], 'flagged @' . $post->getId() . ' for moderator attention') !== false);
	}

	public function testDoubleFlagging()
	{
		$this->grantAccess('flagPost');
		$post = $this->mockPost($this->mockUser());

		$this->assert->doesNotThrow(function() use ($post)
		{
			Api::run(
				new FlagPostJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		});

		$this->assert->throws(function() use ($post)
		{
			return Api::run(
				new FlagPostJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		}, 'You already flagged this post');
	}
}
