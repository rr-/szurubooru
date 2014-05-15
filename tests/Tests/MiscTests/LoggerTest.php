<?php
class LoggerTest extends AbstractTest
{
	public function testLogging()
	{
		$realLogPath = Logger::getLogPath();

		try
		{
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

	public function testPathChanging()
	{
		$logPath = TextHelper::absolutePath(Core::getConfig()->rootDir . '/tests/logs/{yyyy}-{mm}-{dd}.log');
		$realLogPath = TextHelper::absolutePath(Core::getConfig()->rootDir . '/tests/logs/' . date('Y-m-d') . '.log');

		Core::getConfig()->main->logsPath = $logPath;
		$this->assert->doesNotThrow(function()
		{
			Logger::init();
		});
		$this->assert->areEqual($realLogPath, Logger::getLogPath());
	}

	public function testDiscarding()
	{
		$realLogPath = Logger::getLogPath();

		$this->assert->isFalse(file_exists($realLogPath));

		Logger::bufferChanges();
		Logger::log('line 1');
		Logger::log('line 2');
		Logger::log('line 3');
		Logger::discardBuffer();

		$this->assert->isFalse(file_exists($realLogPath));
		Logger::log('line 4');
		Logger::flush();
		$this->assert->isTrue(file_exists($realLogPath));

		$x = file_get_contents($realLogPath);
		$this->assert->isTrue(strpos($x, 'line 1') === false);
		$this->assert->isTrue(strpos($x, 'line 2') === false);
		$this->assert->isTrue(strpos($x, 'line 3') === false);
		$this->assert->isTrue(strpos($x, 'line 4') !== false);
	}
}
