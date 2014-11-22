<?php
namespace Szurubooru\Tests;
use Szurubooru\Dispatcher;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\RouteRepository;
use Szurubooru\Router;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\TokenService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class DispatcherTest extends AbstractDatabaseTestCase
{
	private $routerMock;
	private $configMock;
	private $httpHelperMock;
	private $authServiceMock;
	private $tokenServiceMock;
	private $routeRepositoryMock;

	public function setUp()
	{
		parent::setUp();
		$this->routerMock = $this->mock(Router::class);
		$this->configMock = $this->mockConfig();
		$this->httpHelperMock = $this->mock(HttpHelper::class);
		$this->authServiceMock = $this->mock(AuthService::class);
		$this->tokenServiceMock = $this->mock(TokenService::class);
		$this->routeRepositoryMock = $this->mock(RouteRepository::class);
		$this->configMock->set('misc/dumpSqlIntoQueries', 0);
	}

	public function testDispatchingArrays()
	{
		$expected = ['test' => 'toy'];

		$this->httpHelperMock
			->expects($this->exactly(2))
			->method('setResponseCode')
			->withConsecutive([$this->equalTo(500)], [$this->equalTo(200)]);
		$this->routerMock->expects($this->once())->method('handle')->willReturn($expected);
		$this->routeRepositoryMock->expects($this->once())->method('injectRoutes');

		$dispatcher = $this->getDispatcher();
		$actual = $dispatcher->run('GET', '/');

		unset($actual['__time']);
		$this->assertEquals($expected, $actual);
	}

	public function testDispatchingObjects()
	{
		$classData = new \StdClass;
		$classData->bunny = 5;
		$expected = ['bunny' => 5];

		$this->routerMock->expects($this->once())->method('handle')->willReturn($classData);
		$this->routeRepositoryMock->expects($this->once())->method('injectRoutes');

		$dispatcher = $this->getDispatcher();
		$actual = $dispatcher->run('GET', '/');

		unset($actual['__time']);
		$this->assertEquals($expected, $actual);
	}

	public function testAuthorization()
	{
		$this->httpHelperMock->expects($this->once())->method('getRequestHeader')->with($this->equalTo('X-Authorization-Token'))->willReturn('test');
		$this->tokenServiceMock->expects($this->once())->method('getByName');
		$this->routeRepositoryMock->expects($this->once())->method('injectRoutes');

		$dispatcher = $this->getDispatcher();
		$dispatcher->run('GET', '/');
	}

	private function getDispatcher()
	{
		return new Dispatcher(
			$this->routerMock,
			$this->configMock,
			$this->databaseConnection,
			$this->httpHelperMock,
			$this->authServiceMock,
			$this->tokenServiceMock,
			$this->routeRepositoryMock);
	}
}
