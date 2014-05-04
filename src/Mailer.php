<?php
class Mailer
{
	private static $mailCounter = 0;

	public static function getMailCounter()
	{
		return self::$mailCounter;
	}

	public static function sendMail(Mail $mail, array $tokens = [])
	{
		if (!isset($tokens['host']))
			$tokens['host'] = $_SERVER['HTTP_HOST'];

		if (!isset($tokens['nl']))
			$tokens['nl'] = PHP_EOL;

		$body = wordwrap(TextHelper::replaceTokens($mail->body, $tokens), 70);
		$subject = TextHelper::replaceTokens($mail->subject, $tokens);
		$senderName = TextHelper::replaceTokens($mail->senderName, $tokens);
		$senderEmail = TextHelper::replaceTokens($mail->senderEmail, $tokens);
		$recipientEmail = $mail->recipientEmail;

		if (empty($recipientEmail))
			throw new SimpleException('Destination e-mail address was not found');

		$messageId = $_SERVER['REQUEST_TIME'] . md5($_SERVER['REQUEST_TIME']) . '@' . $_SERVER['HTTP_HOST'];

		$headers = [];
		$headers []= sprintf('MIME-Version: 1.0');
		$headers []= sprintf('Content-Transfer-Encoding: 7bit');
		$headers []= sprintf('Date: %s', date('r', $_SERVER['REQUEST_TIME']));
		$headers []= sprintf('Message-ID: <%s>', $messageId);
		$headers []= sprintf('From: %s <%s>', $senderName, $senderEmail);
		$headers []= sprintf('Reply-To: %s', $senderEmail);
		$headers []= sprintf('Return-Path: %s', $senderEmail);
		$headers []= sprintf('Subject: %s', $subject);
		$headers []= sprintf('Content-Type: text/plain; charset=utf-8', $subject);
		$headers []= sprintf('X-Mailer: PHP/%s', phpversion());
		$headers []= sprintf('X-Originating-IP: %s', $_SERVER['SERVER_ADDR']);
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		mail($recipientEmail, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $senderEmail);

		self::$mailCounter ++;

		LogHelper::log('Sending e-mail with subject "{subject}" to {mail}', [
			'subject' => $subject,
			'mail' => $recipientEmail]);
	}

	public static function sendMailWithTokenLink(
		UserEntity $user,
		$linkDestination,
		Mail $mail,
		array $tokens = [])
	{
		//prepare unique user token
		$token = TokenModel::spawn();
		$token->setUser($user);
		$token->token = TokenModel::forgeUnusedToken();
		$token->used = false;
		$token->expires = null;
		TokenModel::save($token);

		$tokens['link'] = \Chibi\Router::linkTo($linkDestination, ['token' => $token->token]);

		return self::sendMail($mail, $tokens);
	}
}
