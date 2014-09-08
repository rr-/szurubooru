<?php
namespace Szurubooru\Tests\Service;

class PasswordServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testGeneratingPasswords()
	{
		$configMock = $this->mockConfig();
		$passwordService = new \Szurubooru\Services\PasswordService($configMock);

		$sampleCount = 10000;
		$distribution = [];
		for ($i = 0; $i < $sampleCount; $i ++)
		{
			$password = $passwordService->getRandomPassword();
			for ($j = 0; $j < strlen($password); $j ++)
			{
				$c = $password{$j};
				if (!isset($distribution[$j]))
					$distribution[$j] = [$c => 1];
				elseif (!isset($distribution[$j][$c]))
					$distribution[$j][$c] = 1;
				else
					$distribution[$j][$c] ++;
			}
		}

		foreach ($distribution as $index => $characterDistribution)
		{
			$this->assertLessThan(10, $this->getRelativeStandardDeviation($characterDistribution));
		}
	}

	private function getStandardDeviation($sample)
	{
		$mean = array_sum($sample) / count($sample);
		foreach ($sample as $key => $num)
			$devs[$key] = pow($num - $mean, 2);
		return sqrt(array_sum($devs) / (count($devs)));
	}

	private function getRelativeStandardDeviation($sample)
	{
		$mean = array_sum($sample) / count($sample);
		return 100 * $this->getStandardDeviation($sample) / $mean;
	}
}
