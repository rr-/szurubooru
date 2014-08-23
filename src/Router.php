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
		$this->any('/api', ['ApiController', 'runAction']);
	}

	private function registerStaticPages()
	{
		$this->get('', ['StaticPagesController', 'mainPageView']);
		$this->get('/index', ['StaticPagesController', 'mainPageView']);
		$this->get('/api-docs', ['StaticPagesController', 'apiDocsView']);
		$this->get('/help', ['StaticPagesController', 'helpView']);
		$this->get('/help/{tab}', ['StaticPagesController', 'helpView']);
		$this->post('/fatal-error/{code}', ['StaticPagesController', 'fatalErrorView']);
		$this->get('/fatal-error/{code}', ['StaticPagesController', 'fatalErrorView']);
	}

	private function registerAuth()
	{
		$this->get('/auth/login', ['AuthController', 'loginView']);
		$this->post('/auth/login', ['AuthController', 'loginAction']);
		$this->post('/auth/logout', ['AuthController', 'logoutAction']);
		$this->get('/auth/logout', ['AuthController', 'logoutAction']);
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

		$this->get('/posts/upload', ['PostController', 'uploadView'], $postValidation);
		$this->post('/posts/upload', ['PostController', 'uploadAction'], $postValidation);
		$this->get('/post/{identifier}/edit', ['PostController', 'editView'], $postValidation);
		$this->post('/post/{identifier}/edit', ['PostController', 'editAction'], $postValidation);
		$this->any('/post/{identifier}/delete', ['PostController', 'deleteAction'], $postValidation);

		$this->get('/posts/upload/thumb/{url}', ['PostController', 'uploadThumbnailView'], ['url' => '.*']);
		$this->get('/{source}', ['PostController', 'listView'], $postValidation);
		$this->get('/{source}/{page}', ['PostController', 'listView'], $postValidation);
		$this->get('/{source}/{query}/{page}', ['PostController', 'listView'], $postValidation);
		$this->get('/{source}/{query}/{additionalInfo}/{page}', ['PostController', 'listView'], $postValidation);
		$this->post('/{source}-redirect', ['PostController', 'listRedirectAction'], $postValidation);

		$this->get('/post/{name}/retrieve', ['PostController', 'fileView'], $postValidation);
		$this->get('/post/{identifier}', ['PostController', 'genericView'], $postValidation);
		$this->get('/post/{identifier}/search={query}', ['PostController', 'genericView'], $postValidation);
		$this->get('/post/{name}/thumb', ['PostController', 'thumbnailView'], $postValidation);

		$this->any('/post/{identifier}/toggle-tag/{tag}/{enable}', ['PostController', 'toggleTagAction'], $postValidation);
		$this->any('/post/{identifier}/flag', ['PostController', 'flagAction'], $postValidation);
		$this->any('/post/{identifier}/hide', ['PostController', 'hideAction'], $postValidation);
		$this->any('/post/{identifier}/unhide', ['PostController', 'unhideAction'], $postValidation);
		$this->any('/post/{identifier}/rem-fav', ['PostController', 'removeFavoriteAction'], $postValidation);
		$this->any('/post/{identifier}/add-fav', ['PostController', 'addFavoriteAction'], $postValidation);
		$this->any('/post/{identifier}/score/{score}', ['PostController', 'scoreAction'], $postValidation);
		$this->any('/post/{identifier}/feature', ['PostController', 'featureAction'], $postValidation);
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

		$this->get('/users', ['UserController', 'listView'], $userValidation);
		$this->get('/users/{page}', ['UserController', 'listView'], $userValidation);
		$this->get('/users/{filter}/{page}', ['UserController', 'listView'], $userValidation);
		$this->get('/user/{identifier}/{tab}', ['UserController', 'genericView'], $userValidation);
		$this->get('/user/{identifier}/{tab}/{page}', ['UserController', 'genericView'], $userValidation);

		$this->post('/user/{identifier}/edit', ['UserController', 'editAction'], $userValidation);

		$this->get('/register', ['UserController', 'registrationView'], $userValidation);
		$this->post('/register', ['UserController', 'registrationAction'], $userValidation);

		$this->get('/activation', ['UserController', 'activationView'], $userValidation);
		$this->post('/activation', ['UserController', 'activationAction'], $userValidation);
		$this->get('/activation/{tokenText}', ['UserController', 'activationAction'], $userValidation);
		$this->get('/password-reset', ['UserController', 'passwordResetView'], $userValidation);
		$this->post('/password-reset', ['UserController', 'passwordResetAction'], $userValidation);
		$this->get('/password-reset/{tokenText}', ['UserController', 'passwordResetAction'], $userValidation);

		$this->any('/user/{identifier}/flag', ['UserController', 'flagAction'], $userValidation);
		$this->any('/user/{identifier}/ban', ['UserController', 'banAction'], $userValidation);
		$this->any('/user/{identifier}/unban', ['UserController', 'unbanAction'], $userValidation);
		$this->any('/user/{identifier}/accept-registration', ['UserController', 'acceptRegistrationAction'], $userValidation);
		$this->any('/user/{identifier}/delete', ['UserController', 'deleteAction'], $userValidation);
		$this->any('/user/{identifier}/settings', ['UserController', 'settingsAction'], $userValidation);
		$this->any('/user/toggle-safety/{safety}', ['UserController', 'toggleSafetyAction'], $userValidation);
	}

	private function registerLogController()
	{
		$logValidation =
		[
			'name' => '[0-9a-zA-Z._-]+',
			'page' => '\d*',
			'filter' => '.*',
		];

		$this->get('/logs', ['LogController', 'listView'], $logValidation);
		$this->get('/log/{name}', ['LogController', 'logView'], $logValidation);
		$this->get('/log/{name}/{page}', ['LogController', 'logView'], $logValidation);
		$this->get('/log/{name}/{page}/{filter}', ['LogController', 'logView'], $logValidation);
	}

	private function registerTagController()
	{
		$tagValidation =
		[
			'page' => '\d*',
			'filter' => '[^\/]+',
		];

		$this->get('/tags', ['TagController', 'listView'], $tagValidation);
		$this->get('/tags/{page}', ['TagController', 'listView'], $tagValidation);
		$this->get('/tags/{filter}/{page}', ['TagController', 'listView'], $tagValidation);
		$this->get('/tags-autocomplete', ['TagController', 'autoCompleteView'], $tagValidation);
		$this->get('/tags-related', ['TagController', 'relatedView'], $tagValidation);
		$this->get('/tags-rename', ['TagController', 'renameView'], $tagValidation);
		$this->post('/tags-rename', ['TagController', 'renameAction'], $tagValidation);
		$this->get('/tags-merge', ['TagController', 'mergeView'], $tagValidation);
		$this->post('/tags-merge', ['TagController', 'mergeAction'], $tagValidation);
		$this->get('/mass-tag-redirect', ['TagController', 'massTagRedirectView'], $tagValidation);
	}

	private function registerCommentController()
	{
		$commentValidation =
		[
			'id' => '\d+',
			'page' => '\d+',
		];

		$this->get('/comments', ['CommentController', 'listView'], $commentValidation);
		$this->get('/comments/{page}', ['CommentController', 'listView'], $commentValidation);
		$this->post('/comment/add', ['CommentController', 'addAction'], $commentValidation);
		$this->any('/comment/{id}/delete', ['CommentController', 'deleteAction'], $commentValidation);
		$this->get('/comment/{id}/edit', ['CommentController', 'editView'], $commentValidation);
		$this->post('/comment/{id}/edit', ['CommentController', 'editAction'], $commentValidation);
	}
}
