<?php
class Messenger
{
	public static function message($message, $success = true)
	{
		if (empty($message))
			return;

		$context = getContext();

		if (!preg_match('/[.?!]$/', $message))
			$message .= '.';

		$context->transport->success = $success;
		$context->transport->message = $message;
		$context->transport->messageHtml = TextHelper::parseMarkdown($message, true);
	}
}
