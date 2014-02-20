<?php
class Bootstrap
{
	public function render($callback = null)
	{
		if ($callback === null)
		{
			$callback = function()
			{
				(new \Chibi\View())->renderFile($this->context->layoutName);
			};
		}

		if ($this->context->layoutName == 'layout-normal')
		{
			ob_start(['LayoutHelper', 'transformHtml']);
			$callback();
			ob_end_flush();
		}
		else
		{
			$callback();
		}
	}

	public function workWrapper($workCallback)
	{
		$this->config->chibi->baseUrl = 'http://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/';
		session_start();

		$this->context->handleExceptions = false;
		LayoutHelper::setTitle($this->config->main->title);

		$this->context->json = isset($_GET['json']);
		$this->context->layoutName = $this->context->json
			? 'layout-json'
			: 'layout-normal';
		$this->context->transport = new StdClass;
		StatusHelper::init();

		AuthController::doLogIn();

		if (empty($this->context->route))
		{
			http_response_code(404);
			$this->context->viewName = 'error-404';
			$this->render();
			return;
		}

		try
		{
			$this->render($workCallback);
		}
		catch (\Chibi\MissingViewFileException $e)
		{
			$this->context->json = true;
			$this->context->layoutName = 'layout-json';
			$this->render();
		}
		catch (SimpleException $e)
		{
			if ($e instanceof SimpleNotFoundException)
				http_response_code(404);
			StatusHelper::failure($e->getMessage());
			if (!$this->context->handleExceptions)
				$this->context->viewName = 'message';
			$this->render();
		}
		catch (Exception $e)
		{
			StatusHelper::failure($e->getMessage());
			$this->context->transport->exception = $e;
			$this->context->transport->queries = Database::getLogs();
			$this->context->viewName = 'error-exception';
			$this->render();
		}

		AuthController::observeWorkFinish();
	}
}
