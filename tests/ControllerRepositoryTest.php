<?php
namespace Szurubooru\Tests;

class ControllerRepositoryTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testInjection()
	{
		$controllerRepository = \Szurubooru\Injector::get(\Szurubooru\ControllerRepository::class);
		$this->assertNotEmpty($controllerRepository->getControllers());
	}
}
