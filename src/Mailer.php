<?php
class Mailer
{
	private static $mailCounter;
	private static $mailsSent;
	private static $mock;

	public static function init()
	{
		self::$mailCounter = 0;
		self::$mailsSent = [];
		self::$mock = false;
	}

	public static function getMailCounter()
	{
		return self::$mailCounter;
	}

	public static function getMailsSent()
	{
		return self::$mailsSent;
	}

	public static function mockSending()
	{
		self::$mock = true;
	}

	public static function sendMail(Mail $mail, array $tokens = [])
	{
		$host = isset($_SERVER['HTTP_HOST'])
			? $_SERVER['HTTP_HOST']
			: '';
		$ip = isset($_SERVER['SERVER_ADDR'])
			? $_SERVER['SERVER_ADDR']
			: '';

		if (!isset($tokens['host']))
			$tokens['host'] = $host;

		if (!isset($tokens['nl']))
			$tokens['nl'] = PHP_EOL;

		$body = wordwrap(TextHelper::replaceTokens($mail->body, $tokens), 70);
		$subject = TextHelper::replaceTokens($mail->subject, $tokens);
		$senderName = TextHelper::replaceTokens($mail->senderName, $tokens);
		$senderEmail = TextHelper::replaceTokens($mail->senderEmail, $tokens);
		$recipientEmail = $mail->recipientEmail;

		if (empty($recipientEmail))
			throw new SimpleException('Destination e-mail address was not found');

		$messageId = $_SERVER['REQUEST_TIME'] . md5($_SERVER['REQUEST_TIME']) . '@' . $host;

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
		$headers []= sprintf('X-Originating-IP: %s', $ip);
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

		if (!self::$mock)
			mail($recipientEmail, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $senderEmail);

		$mail->tokens = $tokens;
		self::$mailsSent []= $mail;
		self::$mailCounter ++;

		Logger::log('Sending e-mail with subject "{subject}" to {mail}', [
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
		$token->setUsed(false);
		$token->setExpirationTime(null);
		TokenModel::save($token);

		$tokens['link'] = Core::getRouter()->linkTo($linkDestination, ['tokenText' => $token->getText()]);
		$tokens['token'] = $token->getText(); //yeah

		return self::sendMail($mail, $tokens);
	}
}
