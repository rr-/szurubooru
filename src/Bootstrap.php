<?php
class Bootstrap
{
	public function workWrapper($workCallback)
	{
		session_start();
		$this->context->layoutName = isset($_GET['json'])
			? 'layout-json'
			: 'layout-normal';
		$this->context->transport = new StdClass;
		$this->context->transport->success = null;

		$this->config->chibi->baseUrl = 'http://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/';
		R::setup('sqlite:' . $this->config->main->dbPath);
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
