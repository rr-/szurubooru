<?php
namespace Szurubooru\Tests;

require_once __DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'vendor'
	. DIRECTORY_SEPARATOR . 'autoload.php';

class ControllerRepositoryTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testInjection()
	{
		$controllerRepository = \Szurubooru\Injector::get(\Szurubooru\ControllerRepository::class);
		$this->assertNotEmpty($controllerRepository->getControllers());
	}
}
