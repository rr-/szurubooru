<?php
namespace Szurubooru\Tests;

final class DispatcherTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testDispatchingArrays()
	{
		$expected = ['test' => 'toy'];

		$httpHelperMock = $this->getHttpHelperMock();
		$httpHelperMock
			->expects($this->exactly(2))
			->method('setResponseCode')
			->withConsecutive([$this->equalTo(500)], [$this->equalTo(200)]);

		$routerMock = $this->getRouterMock();
		$routerMock->expects($this->once())->method('handle')->willReturn($expected);

		$controllerRepositoryMock = $this->getControllerRepositoryMock();
		$controllerRepositoryMock->method('getControllers')->willReturn([]);

		$dispatcher = new \Szurubooru\Dispatcher($routerMock, $httpHelperMock, $controllerRepositoryMock);
		$actual = $dispatcher->run();

		unset($actual['__time']);
		$this->assertEquals($expected, $actual);
	}

	public function testDispatchingObjects()
	{
		$classData = new \StdClass;
		$classData->bunny = 5;
		$expected = ['bunny' => 5];

		$httpHelperMock = $this->getHttpHelperMock();

		$routerMock = $this->getRouterMock();
		$routerMock->expects($this->once())->method('handle')->willReturn($classData);

		$controllerRepositoryMock = $this->getControllerRepositoryMock();
		$controllerRepositoryMock->method('getControllers')->willReturn([]);

		$dispatcher = new \Szurubooru\Dispatcher($routerMock, $httpHelperMock, $controllerRepositoryMock);
		$actual = $dispatcher->run();

		unset($actual['__time']);
		$this->assertEquals($expected, $actual);
	}

	private function getHttpHelperMock()
	{
		return $this->getMockBuilder(\Szurubooru\Helpers\HttpHelper::class)->disableOriginalConstructor()->getMock();
	}

	private function getRouterMock()
	{
		return $this->getMockBuilder(\Szurubooru\Router::class)->disableOriginalConstructor()->getMock();
	}

	private function getControllerRepositoryMock()
	{
		return $this->getMockBuilder(\Szurubooru\ControllerRepository::class)->disableOriginalConstructor()->getMock();
	}
}
