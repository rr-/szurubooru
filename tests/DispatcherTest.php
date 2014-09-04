<?php
namespace Szurubooru\Tests;

final class DispatcherTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $routerMock;
	private $httpHelperMock;
	private $authServiceMock;
	private $controllerRepositoryMock;

	public function setUp()
	{
		$this->routerMock = $this->mock(\Szurubooru\Router::class);
		$this->httpHelperMock = $this->mock(\Szurubooru\Helpers\HttpHelper::class);
		$this->authServiceMock = $this->mock(\Szurubooru\Services\AuthService::class);
		$this->controllerRepositoryMock = $this->mock(\Szurubooru\ControllerRepository::class);
	}

	public function testDispatchingArrays()
	{
		$expected = ['test' => 'toy'];

		$this->httpHelperMock
			->expects($this->exactly(2))
			->method('setResponseCode')
			->withConsecutive([$this->equalTo(500)], [$this->equalTo(200)]);
		$this->routerMock->expects($this->once())->method('handle')->willReturn($expected);
		$this->controllerRepositoryMock->method('getControllers')->willReturn([]);

		$dispatcher = $this->getDispatcher();
		$actual = $dispatcher->run();

		unset($actual['__time']);
		$this->assertEquals($expected, $actual);
	}

	public function testDispatchingObjects()
	{
		$classData = new \StdClass;
		$classData->bunny = 5;
		$expected = ['bunny' => 5];

		$this->routerMock->expects($this->once())->method('handle')->willReturn($classData);
		$this->controllerRepositoryMock->method('getControllers')->willReturn([]);

		$dispatcher = $this->getDispatcher();
		$actual = $dispatcher->run();

		unset($actual['__time']);
		$this->assertEquals($expected, $actual);
	}

	private function getDispatcher()
	{
		return new \Szurubooru\Dispatcher(
			$this->routerMock,
			$this->httpHelperMock,
			$this->authServiceMock,
			$this->controllerRepositoryMock);
	}
}
