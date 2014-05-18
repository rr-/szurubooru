<?php
class Messenger
{
	public static function success($message)
	{
		self::message($message, true);
	}

	public static function fail($message)
	{
		self::message($message, false);
	}

	private static function message($message, $success = true)
	{
		$context = Core::getContext();

		$message = $message ?: 'Empty message';

		if (!preg_match('/[.?!]$/', $message))
			$message .= '.';

		$context->transport->success = $success;
		$context->transport->message = $message;
		$context->transport->messageHtml = TextHelper::parseMarkdown($message, true);
	}
}
