<?php
require_once '../src/core.php';

if (isset($_SERVER['REDIRECT_URL']))
	$query = rtrim($_SERVER['REDIRECT_URL'], '/');
else
	$query = rtrim($_SERVER['REQUEST_URI'], '/');
$context = Core::getContext();
$context->query = $query;

function renderView()
{
	$context = Core::getContext();
	\Chibi\View::render($context->layoutName, $context);
}

$context->simpleControllerName = null;
$context->simpleActionName = null;

\Chibi\Router::setObserver(function($route, $args)
{
	$context = Core::getContext();
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

Assets::setTitle(Core::getConfig()->main->title);

$context->handleExceptions = false;
$context->layoutName
	= isset($_SERVER['HTTP_X_AJAX'])
	? 'layout-json'
	: 'layout-normal';
$context->viewName = '';
$context->transport = new StdClass;

SessionHelper::init();
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
	Messenger::message($e->getMessage(), false);
	$context->transport->exception = $e;
	$context->transport->queries = \Chibi\Database::getLogs();
	$context->viewName = 'error-exception';
	renderView();
}
