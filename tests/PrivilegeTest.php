<?php
namespace Szurubooru\Tests;

class PrivilegeTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testConstNaming()
	{
		$refl = new \ReflectionClass(\Szurubooru\Privilege::class);
		foreach ($refl->getConstants() as $key => $value)
		{
			$value = strtoupper('privilege_' . ltrim(preg_replace('/[A-Z]/', '_\0', $value), '_'));
			$this->assertEquals($key, $value);
		}
	}

	public function testConfigSectionNaming()
	{
		$refl = new \ReflectionClass(\Szurubooru\Privilege::class);
		$constants = array_values($refl->getConstants());

		$configPath = __DIR__
			. DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . 'data'
			. DIRECTORY_SEPARATOR . 'config.ini';

		$config = new \Szurubooru\Config();
		$config->loadFromIni($configPath);
		foreach ($config->security->privileges as $key => $value)
		{
			$this->assertTrue(in_array($key, $constants), "$key not in constants");
		}
	}
}
