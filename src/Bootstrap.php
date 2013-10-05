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
		catch (Exception $e)
		{
			$this->context->exception = $e;
			$this->context->viewName = 'error-exception';
			(new \Chibi\View())->renderFile($this->context->layoutName);
		}
	}
}
