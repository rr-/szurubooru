<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Controllers\ViewProxies\TagViewProxy;

abstract class AbstractTagRoute extends AbstractRoute
{
	protected function getFullFetchConfig()
	{
		return
		[
			TagViewProxy::FETCH_IMPLICATIONS => true,
			TagViewProxy::FETCH_SUGGESTIONS => true,
			TagViewProxy::FETCH_HISTORY => true,
		];
	}
}
