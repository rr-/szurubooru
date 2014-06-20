<?php
class Router extends \Chibi\Routing\Router
{
	public function __construct()
	{
		$this->registerMisc();
		$this->registerStaticPages();
		$this->registerAuth();
		$this->registerPostController();
		$this->registerUserController();
		$this->registerLogController();
		$this->registerTagController();
		$this->registerCommentController();
	}

	private function registerMisc()
	{
		$this->register(['ApiController', 'runAction'], null, '/api');
	}

	private function registerStaticPages()
	{
		$this->register(['StaticPagesController', 'mainPageView'], 'GET', '');
		$this->register(['StaticPagesController', 'mainPageView'], 'GET', '/index');
		$this->register(['StaticPagesController', 'apiDocsView'], 'GET', '/api-docs');
		$this->register(['StaticPagesController', 'helpView'], 'GET', '/help');
		$this->register(['StaticPagesController', 'helpView'], 'GET', '/help/{tab}');
		$this->register(['StaticPagesController', 'fatalErrorView'], 'POST', '/fatal-error/{code}');
		$this->register(['StaticPagesController', 'fatalErrorView'], 'GET', '/fatal-error/{code}');
	}

	private function registerAuth()
	{
		$this->register(['AuthController', 'loginView'], 'GET', '/auth/login');
		$this->register(['AuthController', 'loginAction'], 'POST', '/auth/login');
		$this->register(['AuthController', 'logoutAction'], 'POST', '/auth/logout');
		$this->register(['AuthController', 'logoutAction'], 'GET', '/auth/logout');
	}

	private function registerPostController()
	{
		$postValidation =
		[
			'tag' => '[^\/]*',
			'enable' => '0|1',
			'source' => 'posts|mass-tag',
			'query' => '[^\/]*',
			'additionalInfo' => '[^\/]*',
			'score' => '-1|0|1',
			'page' => '\d*',
		];

		$this->register(['PostController', 'uploadView'], 'GET', '/posts/upload', $postValidation);
		$this->register(['PostController', 'uploadAction'], 'POST', '/posts/upload', $postValidation);
		$this->register(['PostController', 'editView'], 'GET', '/post/{identifier}/edit', $postValidation);
		$this->register(['PostController', 'editAction'], 'POST', '/post/{identifier}/edit', $postValidation);
		$this->register(['PostController', 'deleteAction'], null, '/post/{identifier}/delete', $postValidation);

		$this->register(['PostController', 'uploadThumbnailView'], 'GET', '/posts/upload/thumb/{url}', ['url' => '.*']);
		$this->register(['PostController', 'listView'], 'GET', '/{source}', $postValidation);
		$this->register(['PostController', 'listView'], 'GET', '/{source}/{page}', $postValidation);
		$this->register(['PostController', 'listView'], 'GET', '/{source}/{query}/{page}', $postValidation);
		$this->register(['PostController', 'listView'], 'GET', '/{source}/{query}/{additionalInfo}/{page}', $postValidation);
		$this->register(['PostController', 'listRedirectAction'], 'POST', '/{source}-redirect', $postValidation);

		$this->register(['PostController', 'randomView'], 'GET', '/random', $postValidation);
		$this->register(['PostController', 'randomView'], 'GET', '/random/{page}', $postValidation);
		$this->register(['PostController', 'favoritesView'], 'GET', '/favorites', $postValidation);
		$this->register(['PostController', 'favoritesView'], 'GET', '/favorites/{page}', $postValidation);
		$this->register(['PostController', 'upvotedView'], 'GET', '/upvoted', $postValidation);
		$this->register(['PostController', 'upvotedView'], 'GET', '/upvoted/{page}', $postValidation);

		$this->register(['PostController', 'genericView'], 'GET', '/post/{identifier}', $postValidation);
		$this->register(['PostController', 'fileView'], 'GET', '/post/{name}/retrieve', $postValidation);
		$this->register(['PostController', 'thumbnailView'], 'GET', '/post/{name}/thumb', $postValidation);

		$this->register(['PostController', 'toggleTagAction'], null, '/post/{identifier}/toggle-tag/{tag}/{enable}', $postValidation);
		$this->register(['PostController', 'flagAction'], null, '/post/{identifier}/flag', $postValidation);
		$this->register(['PostController', 'hideAction'], null, '/post/{identifier}/hide', $postValidation);
		$this->register(['PostController', 'unhideAction'], null, '/post/{identifier}/unhide', $postValidation);
		$this->register(['PostController', 'removeFavoriteAction'], null, '/post/{identifier}/rem-fav', $postValidation);
		$this->register(['PostController', 'addFavoriteAction'], null, '/post/{identifier}/add-fav', $postValidation);
		$this->register(['PostController', 'scoreAction'], null, '/post/{identifier}/score/{score}', $postValidation);
		$this->register(['PostController', 'featureAction'], null, '/post/{identifier}/feature', $postValidation);
	}

	private function registerUserController()
	{
		$userValidation =
		[
			'identifier' => '[^\/]+',
			'page' => '\d*',
			'tab' => 'favs|uploads|settings|edit|delete',
			'filter' => '[^\/]+',
		];

		$this->register(['UserController', 'listView'], 'GET', '/users', $userValidation);
		$this->register(['UserController', 'listView'], 'GET', '/users/{page}', $userValidation);
		$this->register(['UserController', 'listView'], 'GET', '/users/{filter}/{page}', $userValidation);
		$this->register(['UserController', 'genericView'], 'GET', '/user/{identifier}/{tab}', $userValidation);
		$this->register(['UserController', 'genericView'], 'GET', '/user/{identifier}/{tab}/{page}', $userValidation);

		$this->register(['UserController', 'editAction'], 'POST', '/user/{identifier}/edit', $userValidation);

		$this->register(['UserController', 'registrationView'], 'GET', '/register', $userValidation);
		$this->register(['UserController', 'registrationAction'], 'POST', '/register', $userValidation);

		$this->register(['UserController', 'activationView'], 'GET', '/activation', $userValidation);
		$this->register(['UserController', 'activationAction'], 'POST', '/activation', $userValidation);
		$this->register(['UserController', 'activationAction'], 'GET', '/activation/{tokenText}', $userValidation);
		$this->register(['UserController', 'passwordResetView'], 'GET', '/password-reset', $userValidation);
		$this->register(['UserController', 'passwordResetAction'], 'POST', '/password-reset', $userValidation);
		$this->register(['UserController', 'passwordResetAction'], 'GET', '/password-reset/{tokenText}', $userValidation);

		$this->register(['UserController', 'flagAction'], null, '/user/{identifier}/flag', $userValidation);
		$this->register(['UserController', 'banAction'], null, '/user/{identifier}/ban', $userValidation);
		$this->register(['UserController', 'unbanAction'], null, '/user/{identifier}/unban', $userValidation);
		$this->register(['UserController', 'acceptRegistrationAction'], null, '/user/{identifier}/accept-registration', $userValidation);
		$this->register(['UserController', 'deleteAction'], null, '/user/{identifier}/delete', $userValidation);
		$this->register(['UserController', 'settingsAction'], null, '/user/{identifier}/settings', $userValidation);
		$this->register(['UserController', 'toggleSafetyAction'], null, '/user/toggle-safety/{safety}', $userValidation);
	}

	private function registerLogController()
	{
		$this->register(['LogController', 'listView'], 'GET', '/logs');
		$this->register(['LogController', 'logView'], 'GET', '/log/{name}', ['name' => '[0-9a-zA-Z._-]+']);
		$this->register(['LogController', 'logView'], 'GET', '/log/{name}/{page}', ['name' => '[0-9a-zA-Z._-]+', 'page' => '\d*']);
		$this->register(['LogController', 'logView'], 'GET', '/log/{name}/{page}/{filter}', ['name' => '[0-9a-zA-Z._-]+', 'page' => '\d*', 'filter' => '.*']);
	}

	private function registerTagController()
	{
		$tagValidation =
		[
			'page' => '\d*',
			'filter' => '[^\/]+',
		];

		$this->register(['TagController', 'listView'], 'GET', '/tags', $tagValidation);
		$this->register(['TagController', 'listView'], 'GET', '/tags/{page}', $tagValidation);
		$this->register(['TagController', 'listView'], 'GET', '/tags/{filter}/{page}', $tagValidation);
		$this->register(['TagController', 'autoCompleteView'], 'GET', '/tags-autocomplete', $tagValidation);
		$this->register(['TagController', 'relatedView'], 'GET', '/tags-related', $tagValidation);
		$this->register(['TagController', 'renameView'], 'GET', '/tags-rename', $tagValidation);
		$this->register(['TagController', 'renameAction'], 'POST', '/tags-rename', $tagValidation);
		$this->register(['TagController', 'mergeView'], 'GET', '/tags-merge', $tagValidation);
		$this->register(['TagController', 'mergeAction'], 'POST', '/tags-merge', $tagValidation);
		$this->register(['TagController', 'massTagRedirectView'], 'GET', '/mass-tag-redirect', $tagValidation);
	}

	private function registerCommentController()
	{
		$commentValidation =
		[
			'id' => '\d+',
			'page' => '\d+',
		];

		$this->register(['CommentController', 'listView'], 'GET', '/comments', $commentValidation);
		$this->register(['CommentController', 'listView'], 'GET', '/comments/{page}', $commentValidation);
		$this->register(['CommentController', 'addAction'], 'POST', '/comment/add', $commentValidation);
		$this->register(['CommentController', 'deleteAction'], null, '/comment/{id}/delete', $commentValidation);
		$this->register(['CommentController', 'editView'], 'GET', '/comment/{id}/edit', $commentValidation);
		$this->register(['CommentController', 'editAction'], 'POST', '/comment/{id}/edit', $commentValidation);
	}
}
