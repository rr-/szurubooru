<?php
namespace Szurubooru\Routes\Comments;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\ViewProxies\CommentViewProxy;

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
