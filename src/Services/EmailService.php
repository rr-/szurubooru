<?php
namespace Szurubooru\Services;

class EmailService
{
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	public function sendPasswordResetEmail(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Token $token)
	{
		if (!$user->getEmail())
			throw new \BadMethodCall('An activated e-mail addreses is needed to reset the password.');

		$mailSubject = $this->tokenize($this->config->mail->passwordResetSubject);
		$mailBody = $this->tokenizeFile(
			$this->config->mail->passwordResetBodyPath,
			[
				'link' => $this->config->basic->serviceBaseUrl . '#/password-reset/' . $token->getName(),
			]);

		$this->sendEmail($user->getEmail(), $mailSubject, $mailBody);
	}

	public function sendActivationEmail(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Token $token)
	{
		if (!$user->getEmailUnconfirmed())
			throw new \BadMethodCallException('An e-mail address is needed to activate the account.');

		$mailSubject = $this->tokenize($this->config->mail->activationSubject);
		$mailBody = $this->tokenizeFile(
			$this->config->mail->activationBodyPath,
			[
				'link' => $this->config->basic->serviceBaseUrl . '#/activate/' . $token->getName(),
			]);

		$this->sendEmail($user->getEmailUnconfirmed(), $mailSubject, $mailBody);
	}

	private function sendEmail($recipientEmail, $subject, $body)
	{
		$domain = substr($this->config->mail->botEmail, strpos($this->config->mail->botEmail, '@') + 1);

		$clientIp = isset($_SERVER['SERVER_ADDR'])
			? $_SERVER['SERVER_ADDR']
			: '';

		$body = wordwrap($body, 70);
		if (empty($recipientEmail))
			throw new \InvalidArgumentException('Destination e-mail address was not found');

		$messageId = sha1(date('r') . uniqid()) . '@' . $domain;

		$headers = [];
		$headers[] = sprintf('MIME-Version: 1.0');
		$headers[] = sprintf('Content-Transfer-Encoding: 7bit');
		$headers[] = sprintf('Date: %s', date('r'));
		$headers[] = sprintf('Message-ID: <%s>', $messageId);
		$headers[] = sprintf('From: %s <%s>', $this->config->mail->botName, $this->config->mail->botEmail);
		$headers[] = sprintf('Reply-To: %s', $this->config->mail->botEmail);
		$headers[] = sprintf('Return-Path: %s', $this->config->mail->botEmail);
		$headers[] = sprintf('Subject: %s', $subject);
		$headers[] = sprintf('Content-Type: text/plain; charset=utf-8');
		$headers[] = sprintf('X-Mailer: PHP/%s', phpversion());
		$headers[] = sprintf('X-Originating-IP: %s', $clientIp);

		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

		mail($recipientEmail, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $this->config->mail->botEmail);
	}

	private function tokenizeFile($templatePath, $tokens = [])
	{
		$text = file_get_contents($this->config->getDataDirectory() . DIRECTORY_SEPARATOR . $templatePath);
		return $this->tokenize($text, $tokens);
	}

	private function tokenize($text, $tokens = [])
	{
		$tokens['serviceBaseUrl'] = $this->config->basic->serviceBaseUrl;
		$tokens['serviceName'] = $this->config->basic->serviceName;

		foreach ($tokens as $key => $value)
			$text = str_ireplace('{' . $key . '}', $value, $text);

		return $text;
	}
}
