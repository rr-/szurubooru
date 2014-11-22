<?php
namespace Szurubooru\Tests;
use Szurubooru\Router;
use Szurubooru\Tests\AbstractTestCase;
use Szurubooru\Tests\TestController;

final class PostDaoTest extends AbstractTestCase
{
	public function testParameterlessHandling()
	{
		$router = new Router;
		$testOk = false;
		$router->get('/test', function() use (&$testOk) { $testOk = true; });
		$router->handle('GET', '/test');
		$this->assertTrue($testOk);
	}

	public function testTrailingSlashes()
	{
		$router = new Router;
		$testOk = false;
		$router->get('/test', function() use (&$testOk) { $testOk = true; });
		$router->handle('GET', '/test/');
		$this->assertTrue($testOk);
	}

	public function testUnhandledMethod()
	{
		$router = new Router;
		$router->get('/test', function() { $this->fail('Route shouldn\'t be executed'); });
		$this->setExpectedException(\DomainException::class);
		$router->handle('POST', '/test');
	}

	public function testUnhandledQuery()
	{
		$router = new Router;
		$router->get('/test', function() { $this->fail('Route shouldn\'t be executed'); });
		$this->setExpectedException(\DomainException::class);
		$router->handle('GET', '/test2');
	}

	public function testTwoMethodsHandling()
	{
		$router = new Router;
		$testOk = false;
		$router->get('/test', function() { $this->fail('Route shouldn\'t be executed'); });
		$router->post('/test', function() use (&$testOk) { $testOk = true; });
		$router->handle('POST', '/test');
		$this->assertTrue($testOk);
	}

	public function testParameterHandling()
	{
		$router = new Router;
		$testOk = false;
		$router->get('/tests/:id', function($args) use (&$testOk) {
			extract($args);
			$this->assertEquals($id, 'test_id');
			$testOk = true; });

		$router->handle('GET', '/tests/test_id');
		$this->assertTrue($testOk);
	}

	public function testTwoParameterHandling()
	{
		$router = new Router;
		$testOk = false;
		$router->get('/tests/:id/:page', function($args) use (&$testOk) {
			extract($args);
			$this->assertEquals($id, 'test_id');
			$this->assertEquals($page, 'test_page');
			$testOk = true; });

		$router->handle('GET', '/tests/test_id/test_page');
		$this->assertTrue($testOk);
	}

	public function testMissingParameterHandling()
	{
		$router = new Router;
		$testOk = false;
		$router->get('/tests/:id', function($args) use (&$testOk) {
			extract($args);
			$this->assertEquals($id, 'test_id');
			$this->assertFalse(isset($page));
			$testOk = true; });

		$router->handle('GET', '/tests/test_id');
		$this->assertTrue($testOk);
	}

	public function testOutputHandling()
	{
		$router = new Router;
		$router->get('/test', function() { return 'ok'; });
		$output = $router->handle('GET', '/test');
		$this->assertEquals('ok', $output);
	}

	public function testRoutingToClassMethods()
	{
		$router = new Router;
		$testController = new TestController();
		$router->get('/normal', [$testController, 'normalRoute']);
		$router->get('/static', [TestController::class, 'staticRoute']);
		$this->assertEquals('normal', $router->handle('GET', '/normal'));
		$this->assertEquals('static', $router->handle('GET', '/static'));
	}
}

class TestController
{
	public function normalRoute()
	{
		return 'normal';
	}

	public static function staticRoute()
	{
		return 'static';
	}
}
