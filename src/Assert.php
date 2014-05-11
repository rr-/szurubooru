<?php
class Assert
{
	public function throws($callback, $expectedMessage)
	{
		$success = false;
		try
		{
			$callback();
			$success = true;
		}
		catch (Exception $e)
		{
			if (stripos($e->getMessage(), $expectedMessage) === false)
			{
				$this->fail('Assertion failed. Expected: "' . $expectedMessage . '", got: "' . $e->getMessage() . '"'
					. PHP_EOL . $e->getTraceAsString() . PHP_EOL . '---' . PHP_EOL);
			}
		}
		if ($success)
			$this->fail('Assertion failed. Expected exception, got nothing');
	}

	public function doesNotThrow($callback)
	{
		try
		{
			$ret = $callback();
		}
		catch (Exception $e)
		{
			$this->fail('Assertion failed. Expected nothing, got exception: "' . $e->getMessage() . '"'
				. PHP_EOL . $e->getTraceAsString() . PHP_EOL . '---' . PHP_EOL);
		}
		return $ret;
	}

	public function isNull($actual)
	{
		if ($actual !== null and $actual !== false)
			$this->fail('Assertion failed. Expected: NULL, got: "' . $actual . '"');
	}

	public function isNotNull($actual)
	{
		if ($actual === null or $actual === false)
			$this->fail('Assertion failed. Expected: not NULL, got: "' . $actual . '"');
	}

	public function isTrue($actual)
	{
		return $this->areEqual(1, intval(boolval($actual)));
	}

	public function isFalse($actual)
	{
		return $this->areEqual(0, intval(boolval($actual)));
	}

	public function areEqual($expected, $actual)
	{
		if ($expected !== $actual)
			$this->fail('Assertion failed. Expected: "' . $this->dumpVar($expected) . '", got: "' . $this->dumpVar($actual) . '"');
	}

	public function areEquivalent($expected, $actual)
	{
		if ($expected != $actual)
			$this->fail('Assertion failed. Expected: "' . $this->dumpVar($expected) . '", got: "' . $this->dumpVar($actual) . '"');
	}

	public function areNotEqual($expected, $actual)
	{
		if ($expected === $actual)
			$this->fail('Assertion failed. Specified objects are equal');
	}

	public function areNotEquivalent($expected, $actual)
	{
		if ($expected == $actual)
			$this->fail('Assertion failed. Specified objects are equivalent');
	}

	public function dumpVar($var)
	{
		ob_start();
		var_dump($var);
		return trim(ob_get_clean());
	}

	public function fail($message)
	{
		throw new SimpleException($message);
	}
}
