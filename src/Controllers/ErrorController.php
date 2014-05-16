<?php
class ErrorController extends AbstractController
{
	public function simpleExceptionView(Exception $exception)
	{
		if ($exception instanceof SimpleNotFoundException)
			\Chibi\Util\Headers::setCode(404);
		else
			\Chibi\Util\Headers::setCode(400);
		Messenger::fail($exception->getMessage());

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->renderView('message');
	}

	public function seriousExceptionView(Exception $exception)
	{
		\Chibi\Util\Headers::setCode(400);
		Messenger::fail($exception->getMessage());
		$context->transport->exception = $exception;
		$context->transport->queries = \Chibi\Database::getLogs();

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->renderView('error-exception');
	}
}
