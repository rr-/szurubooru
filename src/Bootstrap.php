<?php
class Bootstrap
{
	public function attachUser()
	{
		$this->context->loggedIn = false;
		if (isset($_SESSION['user-id']))
		{
			$this->context->user = R::findOne('user', 'id = ?', [$_SESSION['user-id']]);
			if (!empty($this->context->user))
			{
				$this->context->loggedIn = true;
			}
		}
		if (empty($this->context->user))
		{
			$dummy = R::dispense('user');
			$dummy->name = 'Anonymous';
			$dummy->access_rank = AccessRank::Anonymous;
			$this->context->user = $dummy;
		}
	}

	public function workWrapper($workCallback)
	{
		$this->config->chibi->baseUrl = 'http://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/';
		R::setup('sqlite:' . $this->config->main->dbPath);
		session_start();

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
			'core.js',
		];

		$this->context->layoutName = isset($_GET['json'])
			? 'layout-json'
			: 'layout-normal';
		$this->context->transport = new StdClass;
		$this->context->transport->success = null;

		$this->attachUser();

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
		catch (SimpleException $e)
		{
			$this->context->transport->errorMessage = rtrim($e->getMessage(), '.') . '.';
			$this->context->transport->exception = $e;
			$this->context->transport->success = false;
			(new \Chibi\View())->renderFile($this->context->layoutName);
		}
		catch (Exception $e)
		{
			$this->context->transport->errorMessage = rtrim($e->getMessage(), '.') . '.';
			$this->context->transport->exception = $e;
			$this->context->transport->success = false;
			$this->context->viewName = 'error-exception';
			(new \Chibi\View())->renderFile($this->context->layoutName);
		}
	}
}
