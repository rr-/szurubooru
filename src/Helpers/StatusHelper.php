<?php
class StatusHelper
{
	private static function flag($success, $message = null)
	{
		$context = \Chibi\Registry::getContext();
		if (!empty($message))
		{
			if (!preg_match('/[.?!]$/', $message))
				$message .= '.';

			$context->transport->message = $message;
			$context->transport->messageHtml = TextHelper::parseMarkdown($message, true);
		}
		$context->transport->success = $success;
	}

	public static function init()
	{
		$context = \Chibi\Registry::getContext();
		$context->transport->success = null;
	}

	public static function success($message = null)
	{
		self::flag(true, $message);
	}

	public static function failure($message = null)
	{
		self::flag(false, $message);
	}
}
