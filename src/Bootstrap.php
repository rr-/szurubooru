<?php
class Bootstrap
{
	public function workWrapper($workCallback)
	{
		$this->config->chibi->baseUrl = 'http://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/';
		session_start();

		$this->context->handleExceptions = false;
		$this->context->title = $this->config->main->title;
		$this->context->stylesheets =
		[
			'../lib/jquery-ui/jquery-ui.css',
			'core.css',
		];
		$this->context->scripts =
		[
			'../lib/jquery/jquery.min.js',
			'../lib/jquery-ui/jquery-ui.min.js',
			'../lib/mousetrap/mousetrap.min.js',
			'core.js',
		];

		$this->context->json = isset($_GET['json']);
		$this->context->layoutName = $this->context->json
			? 'layout-json'
			: 'layout-normal';
		$this->context->transport = new StdClass;
		StatusHelper::init();

		AuthController::doLogIn();

		if (empty($this->context->route))
		{
			$this->context->viewName = 'error-404';
			(new \Chibi\View())->renderFile($this->context->layoutName);
			return;
		}

		try
		{
			$workCallback();
		}
		catch (\Chibi\MissingViewFileException $e)
		{
			$this->context->json = true;
			$this->context->layoutName = 'layout-json';
			(new \Chibi\View())->renderFile($this->context->layoutName);
		}
		catch (SimpleException $e)
		{
			StatusHelper::failure(rtrim($e->getMessage(), '.') . '.');
			if (!$this->context->handleExceptions)
				$this->context->viewName = 'message';
			(new \Chibi\View())->renderFile($this->context->layoutName);
		}
		catch (Exception $e)
		{
			StatusHelper::failure(rtrim($e->getMessage(), '.') . '.');
			$this->context->transport->exception = $e;
			$this->context->transport->queries = Database::getLogs();
			$this->context->viewName = 'error-exception';
			(new \Chibi\View())->renderFile($this->context->layoutName);
		}

		AuthController::observeWorkFinish();
	}
}
