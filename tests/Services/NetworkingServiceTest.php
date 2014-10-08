<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Tests\AbstractTestCase;

final class NetworkingServiceTest extends AbstractTestCase
{
	public function testDownload()
	{
		$httpHelper = $this->mock(HttpHelper::class);
		$networkingService = new NetworkingService($httpHelper);
		$content = $networkingService->download('http://modernseoul.files.wordpress.com/2012/04/korean-alphabet-chart-modern-seoul.jpg');
		$this->assertGreaterThan(0, strlen($content));
	}
}
