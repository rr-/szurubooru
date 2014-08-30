<?php
namespace Szurubooru\Tests;

final class PostDaoTest extends \PHPUnit_Framework_TestCase
{
	public function testParameterlessHandling()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/test', function() use (&$testOk) { $testOk = true; });
		$router->handle('GET', '/test');
		$this->assertTrue($testOk);
	}

	public function testTrailingSlashes()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/test', function() use (&$testOk) { $testOk = true; });
		$router->handle('GET', '/test/');
		$this->assertTrue($testOk);
	}

	public function testUnhandledMethod()
	{
		$router = new \Szurubooru\Router;
		$router->get('/test', function() { $this->fail('Route shouldn\'t be executed'); });
		$this->setExpectedException('\DomainException');
		$router->handle('POST', '/test');
	}

	public function testUnhandledQuery()
	{
		$router = new \Szurubooru\Router;
		$router->get('/test', function() { $this->fail('Route shouldn\'t be executed'); });
		$this->setExpectedException('\DomainException');
		$router->handle('GET', '/test2');
	}

	public function testTwoMethodsHandling()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/test', function() { $this->fail('Route shouldn\'t be executed'); });
		$router->post('/test', function() use (&$testOk) { $testOk = true; });
		$router->handle('POST', '/test');
		$this->assertTrue($testOk);
	}

	public function testParameterHandling()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/tests/:id', function($id) use (&$testOk) {
			$this->assertEquals($id, 'test_id');
			$testOk = true; });

		$router->handle('GET', '/tests/test_id');
		$this->assertTrue($testOk);
	}

	public function testTwoParameterHandling()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/tests/:id/:page', function($id, $page) use (&$testOk) {
			$this->assertEquals($id, 'test_id');
			$this->assertEquals($page, 'test_page');
			$testOk = true; });

		$router->handle('GET', '/tests/test_id/test_page');
		$this->assertTrue($testOk);
	}

	public function testMissingParameterHandling()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/tests/:id', function($id, $page) use (&$testOk) {
			$this->assertEquals($id, 'test_id');
			$this->assertNull($page);
			$testOk = true; });

		$router->handle('GET', '/tests/test_id');
		$this->assertTrue($testOk);
	}

	public function testMissingDefaultParameterHandling()
	{
		$router = new \Szurubooru\Router;
		$testOk = false;
		$router->get('/tests/:id', function($id, $page = 1) use (&$testOk) {
			$this->assertEquals($id, 'test_id');
			$this->assertEquals(1, $page);
			$testOk = true; });

		$router->handle('GET', '/tests/test_id');
		$this->assertTrue($testOk);
	}

	public function testOutputHandling()
	{
		$router = new \Szurubooru\Router;
		$router->get('/test', function() { return 'ok'; });
		$output = $router->handle('GET', '/test');
		$this->assertEquals('ok', $output);
	}
}
