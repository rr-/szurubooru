<?php
namespace Szurubooru\Tests\Service;

class PasswordServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;

	public function setUp()
	{
		parent::setUp();
		$this->configMock = $this->mockConfig();
	}

	public function testLegacyPasswordValidation()
	{
		$passwordService = $this->getPasswordService();
		$this->configMock->set('security/secret', 'doesnt matter');
		$this->assertTrue($passwordService->isHashValid('testt', 'ac63e0bcdf20b82db509d123166c4592', '2602572e077d48b35af39d1cff84bfcaa5363116'));
	}

	public function testPasswordValidation()
	{
		$passwordService = $this->getPasswordService();
		$this->configMock->set('security/secret', 'change');
		$this->assertTrue($passwordService->isHashValid('testt', '/', '4f4f8b836cd65f3f1d0b7751fc442f79595c23439cd8a928af15e10807bf08cc'));
	}

	public function testGeneratingPasswords()
	{
		$passwordService = $this->getPasswordService();

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

	private function getPasswordService()
	{
		return new \Szurubooru\Services\PasswordService($this->configMock);
	}
}
