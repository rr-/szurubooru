<?php
abstract class AbstractController
{
	public function workWrapper($workCallback)
	{
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
