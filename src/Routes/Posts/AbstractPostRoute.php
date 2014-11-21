<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;

abstract class AbstractPostRoute extends AbstractRoute
{
	protected function getFullFetchConfig()
	{
		return
		[
			PostViewProxy::FETCH_RELATIONS => true,
			PostViewProxy::FETCH_TAGS => true,
			PostViewProxy::FETCH_USER => true,
			PostViewProxy::FETCH_HISTORY => true,
			PostViewProxy::FETCH_OWN_SCORE => true,
			PostViewProxy::FETCH_FAVORITES => true,
			PostViewProxy::FETCH_NOTES => true,
		];
	}

	protected function getLightFetchConfig()
	{
		return
		[
			PostViewProxy::FETCH_TAGS => true,
		];
	}
}
