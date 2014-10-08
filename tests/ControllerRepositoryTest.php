<?php
namespace Szurubooru\Tests;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractTestCase;

final class ControllerRepositoryTest extends AbstractTestCase
{
	public function testInjection()
	{
		$controllerRepository = Injector::get(\Szurubooru\ControllerRepository::class);
		$this->assertNotEmpty($controllerRepository->getControllers());
	}
}
