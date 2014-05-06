<?php
class LoggerTest extends AbstractTest
{
	public function testLogging()
	{
		$logPath = __DIR__ . '/logs/{yyyy}-{mm}-{dd}.log';
		$realLogPath = __DIR__ . '/logs/' . date('Y-m-d') . '.log';

		try
		{
			getConfig()->main->logsPath = $logPath;
			$this->assert->doesNotThrow(function()
			{
				Logger::init();
			});

			$this->assert->isFalse(file_exists($realLogPath));
			$this->assert->doesNotThrow(function()
			{
				Logger::log('Simple text');
			});
			$this->assert->isTrue(file_exists($realLogPath));

			$x = file_get_contents($realLogPath);
			$this->assert->isTrue(strpos($x, 'Simple text') !== false);
		}
		finally
		{
			if (file_exists($realLogPath))
				unlink($realLogPath);
		}
	}
}
