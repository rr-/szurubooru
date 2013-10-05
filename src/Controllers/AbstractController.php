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
	}
}
