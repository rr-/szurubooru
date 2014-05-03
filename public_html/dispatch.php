<?php
require_once '../src/core.php';

$query = rtrim($_SERVER['REQUEST_URI'], '/');

//prepare context
$context = new StdClass;
$context->startTime = $startTime;
$context->query = $query;

function renderView()
{
	$context = getContext();
	\Chibi\View::render($context->layoutName, $context);
}

function getContext()
{
	global $context;
	return $context;
}

$context->simpleControllerName = null;
$context->simpleActionName = null;

\Chibi\Router::setObserver(function($route, $args)
{
	$context = getContext();
	$context->route = $route;
	list ($className, $methodName) = $route->destination;

	$context->simpleControllerName = TextCaseConverter::convert(
		str_replace('Controller', '', $className),
		TextCaseConverter::CAMEL_CASE,
		TextCaseConverter::SPINAL_CASE);

	$context->simpleActionName = TextCaseConverter::convert(
		preg_replace('/Action|View/', '', $methodName),
		TextCaseConverter::CAMEL_CASE,
		TextCaseConverter::SPINAL_CASE);

	$context->viewName = sprintf(
		'%s-%s',
		$context->simpleControllerName,
		$context->simpleActionName);
});

\Chibi\Router::register(['StaticPagesController', 'mainPageView'], 'GET', '');
\Chibi\Router::register(['StaticPagesController', 'mainPageView'], 'GET', '/index');
\Chibi\Router::register(['StaticPagesController', 'helpView'], 'GET', '/help');
\Chibi\Router::register(['StaticPagesController', 'helpView'], 'GET', '/help/{tab}');

\Chibi\Router::register(['AuthController', 'loginView'], 'GET', '/auth/login');
\Chibi\Router::register(['AuthController', 'loginAction'], 'POST', '/auth/login');
\Chibi\Router::register(['AuthController', 'logoutAction'], 'POST', '/auth/logout');
\Chibi\Router::register(['AuthController', 'logoutAction'], 'GET', '/auth/logout');

\Chibi\Router::register(['LogController', 'listView'], 'GET', '/logs');
\Chibi\Router::register(['LogController', 'logView'], 'GET', '/log/{name}', ['name' => '[0-9a-zA-Z._-]+']);
\Chibi\Router::register(['LogController', 'logView'], 'GET', '/log/{name}/{page}', ['name' => '[0-9a-zA-Z._-]+', 'page' => '\d*']);
\Chibi\Router::register(['LogController', 'logView'], 'GET', '/log/{name}/{page}/{filter}', ['name' => '[0-9a-zA-Z._-]+', 'page' => '\d*', 'filter' => '.*']);

$postValidation =
[
	'tag' => '[^\/]*',
	'enable' => '0|1',
	'source' => 'posts|mass-tag',
	'query' => '[^\/]*',
	'additionalInfo' => '[^\/]*',
	'score' => '-1|0|1',
];

\Chibi\Router::register(['PostController', 'uploadView'], 'GET', '/posts/upload', $postValidation);
\Chibi\Router::register(['PostController', 'uploadAction'], 'POST', '/posts/upload', $postValidation);
\Chibi\Router::register(['PostController', 'editView'], 'GET', '/post/{id}/edit', $postValidation);
\Chibi\Router::register(['PostController', 'editAction'], 'POST', '/post/{id}/edit', $postValidation);
\Chibi\Router::register(['PostController', 'deleteAction'], 'POST', '/post/{id}/delete', $postValidation);

\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}', $postValidation);
\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}/{query}', $postValidation);
\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}/{query}/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'listView'], 'GET', '/{source}/{additionalInfo}/{query}/{page}', $postValidation);

\Chibi\Router::register(['PostController', 'randomView'], 'GET', '/random', $postValidation);
\Chibi\Router::register(['PostController', 'randomView'], 'GET', '/random/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'favoritesView'], 'GET', '/favorites', $postValidation);
\Chibi\Router::register(['PostController', 'favoritesView'], 'GET', '/favorites/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'upvotedView'], 'GET', '/upvoted', $postValidation);
\Chibi\Router::register(['PostController', 'upvotedView'], 'GET', '/upvoted/{page}', $postValidation);
\Chibi\Router::register(['PostController', 'toggleTagAction'], 'POST', '/post/{id}/toggle-tag/{tag}/{enable}', $postValidation);
\Chibi\Router::register(['PostController', 'flagAction'], 'POST', '/post/{id}/flag', $postValidation);
\Chibi\Router::register(['PostController', 'hideAction'], 'POST', '/post/{id}/hide', $postValidation);
\Chibi\Router::register(['PostController', 'unhideAction'], 'POST', '/post/{id}/unhide', $postValidation);
\Chibi\Router::register(['PostController', 'removeFavoriteAction'], 'POST', '/post/{id}/rem-fav', $postValidation);
\Chibi\Router::register(['PostController', 'addFavoriteAction'], 'POST', '/post/{id}/add-fav', $postValidation);
\Chibi\Router::register(['PostController', 'scoreAction'], 'POST', '/post/{id}/score/{score}', $postValidation);
\Chibi\Router::register(['PostController', 'featureAction'], 'POST', '/post/{id}/feature', $postValidation);

\Chibi\Router::register(['CommentController', 'listView'], 'GET', '/comments');
\Chibi\Router::register(['CommentController', 'listView'], 'GET', '/comments/{page}', ['page' => '\d+']);
\Chibi\Router::register(['CommentController', 'addAction'], 'POST', '/comment/add');
\Chibi\Router::register(['CommentController', 'deleteAction'], 'POST', '/comment/{id}/delete', ['id' => '\d+']);
\Chibi\Router::register(['CommentController', 'editView'], 'GET', '/comment/{id}/edit', ['id' => '\d+']);
\Chibi\Router::register(['CommentController', 'editAction'], 'POST', '/comment/{id}/edit', ['id' => '\d+']);

foreach (['GET', 'POST'] as $method)
{
	\Chibi\Router::register(['PostController', 'viewAction'], $method, '/post/{id}', $postValidation);
	\Chibi\Router::register(['PostController', 'retrieveAction'], $method, '/post/{name}/retrieve', $postValidation);
	\Chibi\Router::register(['PostController', 'thumbAction'], $method, '/post/{name}/thumb', $postValidation);

	$tagValidation =
	[
		'page' => '\d*',
		'filter' => '[^\/]+',
	];

	\Chibi\Router::register(['TagController', 'listAction'], $method, '/tags', $tagValidation);
	\Chibi\Router::register(['TagController', 'listAction'], $method, '/tags/{filter}', $tagValidation);
	\Chibi\Router::register(['TagController', 'listAction'], $method, '/tags/{page}', $tagValidation);
	\Chibi\Router::register(['TagController', 'listAction'], $method, '/tags/{filter}/{page}', $tagValidation);
	\Chibi\Router::register(['TagController', 'autoCompleteAction'], $method, '/tags-autocomplete', $tagValidation);
	\Chibi\Router::register(['TagController', 'relatedAction'], $method, '/tags-related', $tagValidation);
	\Chibi\Router::register(['TagController', 'mergeAction'], $method, '/tags-merge', $tagValidation);
	\Chibi\Router::register(['TagController', 'renameAction'], $method, '/tags-rename', $tagValidation);
	\Chibi\Router::register(['TagController', 'massTagRedirectAction'], $method, '/mass-tag-redirect', $tagValidation);

	$userValidations =
	[
		'name' => '[^\/]+',
		'page' => '\d*',
		'tab' => 'favs|uploads',
		'filter' => '[^\/]+',
	];

	\Chibi\Router::register(['UserController', 'registrationAction'], $method, '/register', $userValidations);
	\Chibi\Router::register(['UserController', 'viewAction'], $method, '/user/{name}/{tab}', $userValidations);
	\Chibi\Router::register(['UserController', 'viewAction'], $method, '/user/{name}/{tab}/{page}', $userValidations);
	\Chibi\Router::register(['UserController', 'listAction'], $method, '/users', $userValidations);
	\Chibi\Router::register(['UserController', 'listAction'], $method, '/users/{page}', $userValidations);
	\Chibi\Router::register(['UserController', 'listAction'], $method, '/users/{filter}', $userValidations);
	\Chibi\Router::register(['UserController', 'listAction'], $method, '/users/{filter}/{page}', $userValidations);
	\Chibi\Router::register(['UserController', 'flagAction'], $method, '/user/{name}/flag', $userValidations);
	\Chibi\Router::register(['UserController', 'banAction'], $method, '/user/{name}/ban', $userValidations);
	\Chibi\Router::register(['UserController', 'unbanAction'], $method, '/user/{name}/unban', $userValidations);
	\Chibi\Router::register(['UserController', 'acceptRegistrationAction'], $method, '/user/{name}/accept-registration', $userValidations);
	\Chibi\Router::register(['UserController', 'deleteAction'], $method, '/user/{name}/delete', $userValidations);
	\Chibi\Router::register(['UserController', 'settingsAction'], $method, '/user/{name}/settings', $userValidations);
	\Chibi\Router::register(['UserController', 'editAction'], $method, '/user/{name}/edit', $userValidations);
	\Chibi\Router::register(['UserController', 'activationAction'], $method, '/activation/{token}', $userValidations);
	\Chibi\Router::register(['UserController', 'activationProxyAction'], $method, '/activation-proxy/{token}', $userValidations);
	\Chibi\Router::register(['UserController', 'passwordResetAction'], $method, '/password-reset/{token}', $userValidations);
	\Chibi\Router::register(['UserController', 'passwordResetProxyAction'], $method, '/password-reset-proxy/{token}', $userValidations);
	\Chibi\Router::register(['UserController', 'toggleSafetyAction'], $method, '/user/toggle-safety/{safety}', $userValidations);
}

Assets::setTitle($config->main->title);

$context->handleExceptions = false;
$context->json = isset($_GET['json']);
$context->layoutName = $context->json
	? 'layout-json'
	: 'layout-normal';
$context->viewName = '';
$context->transport = new StdClass;

session_start();
if (!Auth::isLoggedIn())
	Auth::tryAutoLogin();

register_shutdown_function(function()
{
	$error = error_get_last();
	if ($error !== null)
		\Chibi\Util\Headers::setCode(400);
});

try
{
	try
	{
		\Chibi\Router::run($query);
		renderView();
		AuthController::observeWorkFinish();
	}
	catch (\Chibi\UnhandledRouteException $e)
	{
		throw new SimpleNotFoundException($query . ' not found.');
	}
}
catch (\Chibi\MissingViewFileException $e)
{
	$context->json = true;
	$context->layoutName = 'layout-json';
	renderView();
}
catch (SimpleException $e)
{
	if ($e instanceof SimpleNotFoundException)
		\Chibi\Util\Headers::setCode(404);
	else
		\Chibi\Util\Headers::setCode(400);
	Messenger::message($e->getMessage(), false);
	if (!$context->handleExceptions)
		$context->viewName = 'message';
	renderView();
}
catch (Exception $e)
{
	\Chibi\Util\Headers::setCode(400);
	Messenger::message($e->getMessage());
	$context->transport->exception = $e;
	$context->transport->queries = \Chibi\Database::getLogs();
	$context->viewName = 'error-exception';
	renderView();
}
