<?php
abstract class AbstractController
{
	protected function attachUser()
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
			#todo: construct anonymous user
			$this->context->user = null;
		}
	}

	public function workWrapper($workCallback)
	{
		session_start();
		$this->context->layoutName = isset($_GET['json'])
			? 'layout-json'
			: 'layout-normal';
		$this->context->transport = new StdClass;
		$this->context->transport->success = null;

		$this->attachUser();

		try
		{
			$workCallback();
		}
		catch (SimpleException $e)
		{
			$this->context->transport->errorMessage = rtrim($e->getMessage(), '.') . '.';
			$this->context->transport->exception = $e;
			$this->context->transport->success = false;
		}
		catch (Exception $e)
		{
			$this->context->exception = $e;
			$this->context->viewName = 'error-exception';
		}
	}
}
