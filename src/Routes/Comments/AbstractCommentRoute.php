<?php
namespace Szurubooru\Routes\Comments;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Controllers\ViewProxies\CommentViewProxy;

abstract class AbstractCommentRoute extends AbstractRoute
{
	protected function getCommentsFetchConfig()
	{
		return
		[
			CommentViewProxy::FETCH_OWN_SCORE => true,
		];
	}
}
