<?php
namespace Szurubooru\Tests;
use Szurubooru\Injector;
use Szurubooru\RouteRepository;
use Szurubooru\Router;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Tests\AbstractTestCase;

final class RouteRepositoryTest extends AbstractTestCase
{
	public function testFactory()
	{
		$routeRepository = Injector::get(RouteRepository::class);
		$this->assertNotEmpty($routeRepository->getRoutes());
	}

	public function testRouteInjection()
	{
		$routerMock = $this->mock(Router::class);
		$routeMock = $this->mock(AbstractRoute::class);
		$routeMock->expects($this->once())->method('getMethods')->willReturn(['POST', 'GET']);
		$routeMock->expects($this->atLeast(1))->method('getUrl')->willReturn('/test');
		$routerMock->expects($this->once())->method('post')->with('/test', $this->anything());
		$routerMock->expects($this->once())->method('get')->with('/test', $this->anything());
		$routeRepository = new RouteRepository([$routeMock]);
		$routeRepository->injectRoutes($routerMock);
	}

	public function testRouteCallbackInjection()
	{
		$router = new Router();
		$routeMock = $this->mock(AbstractRoute::class);
		$routeMock->expects($this->once())->method('getMethods')->willReturn(['POST', 'GET']);
		$routeMock->expects($this->atLeast(1))->method('getUrl')->willReturn('/test');
		$routeMock->expects($this->atLeast(1))->method('work');
		$routeRepository = new RouteRepository([$routeMock]);
		$routeRepository->injectRoutes($router);
		$router->handle('GET', '/test');
	}
}
