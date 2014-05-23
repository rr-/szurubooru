<?php
class Dispatcher
{
	public function run()
	{
		$query = $this->retrieveQuery();

		$context = Core::getContext();
		$context->query = $query;
		$context->transport = new StdClass;

		$this->setRouterObserver();
		$this->ensureResponseCodeUponFail();

		SessionHelper::init();
		if (!Auth::isLoggedIn())
			Auth::tryAutoLogin();

		$this->routeAndHandleErrors($query);
	}

	private function routeAndHandleErrors($query)
	{
		try
		{
			Core::getRouter()->run($query);
		}
		catch (\Chibi\Routing\UnhandledRouteException $e)
		{
			$errorController = new ErrorController;
			$errorController->simpleExceptionView(new SimpleNotFoundException($query . ' not found.'));
		}
		catch (SimpleException $e)
		{
			$errorController = new ErrorController;
			$errorController->simpleExceptionView($e);
		}
		catch (Exception $e)
		{
			$errorController = new ErrorController;
			$errorController->seriousExceptionView($e);
		}
	}

	private function ensureResponseCodeUponFail()
	{
		register_shutdown_function(function()
		{
			$error = error_get_last();
			if ($error !== null)
				\Chibi\Util\Headers::setCode(400);
		});
	}

	private function retrieveQuery()
	{
		if (isset($_SERVER['REDIRECT_URL']))
			return $this->parseRawHttpQuery($_SERVER['REDIRECT_URL']);
		else
			return $this->parseRawHttpQuery($_SERVER['REQUEST_URI']);
	}

	private function parseRawHttpQuery($rawHttpQuery)
	{
		return rtrim($rawHttpQuery, '/');
	}

	private function setRouterObserver()
	{
		Core::getRouter()->setObserver(function($route, $args)
		{
			$context = Core::getContext();
			$context->route = $route;
		});
	}
}

