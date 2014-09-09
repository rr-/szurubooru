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
		if (!$user->email)
			throw new \BadMethodCall('An activated e-mail addreses is needed to reset the password.');

		$recipientEmail = $user->email;
		$senderName = $this->config->basic->serviceName . ' bot';
		$subject = $this->config->basic->serviceName . ' password reset';

		$body =
			'Hello,' .
			PHP_EOL . PHP_EOL .
			'Someone (probably you) requested to reset password for an account at ' . $this->config->basic->serviceName . '. ' .
			'In order to proceed, please click this link or paste it in your browser address bar: ' .
			PHP_EOL . PHP_EOL .
			$this->config->basic->serviceBaseUrl . '#/password-reset/' . $token->name .
			PHP_EOL . PHP_EOL .
			'Otherwise please ignore this mail.' .
			$this->getFooter();

		$this->sendEmail($senderName, $recipientEmail, $subject, $body);
	}

	public function sendActivationEmail(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Token $token)
	{
		if (!$user->emailUnconfirmed)
		{
			throw new \BadMethodCallException(
				$user->email
					? 'E-mail for this account is already confirmed.'
					: 'An e-mail address is needed to activate the account.');
		}

		$recipientEmail = $user->emailUnconfirmed;
		$senderName = $this->config->basic->serviceName . ' bot';
		$subject = $this->config->basic->serviceName . ' account activation';

		$body =
			'Hello,' .
			PHP_EOL . PHP_EOL .
			'Someone (probably you) registered at ' . $this->config->basic->serviceName . ' an account with this e-mail address. ' .
			'In order to finish activation, please click this link or paste it in your browser address bar: ' .
			PHP_EOL . PHP_EOL .
			$this->config->basic->serviceBaseUrl . '#/activate/' . $token->name .
			PHP_EOL . PHP_EOL .
			'Otherwise please ignore this mail.' .
			$this->getFooter();

		$this->sendEmail($senderName, $recipientEmail, $subject, $body);
	}

	private function sendEmail($senderName, $recipientEmail, $subject, $body)
	{
		$senderEmail = $this->config->basic->emailAddress;
		$domain = substr($senderEmail, strpos($senderEmail, '@') + 1);

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
		$headers[] = sprintf('From: %s <%s>', $senderName, $senderEmail);
		$headers[] = sprintf('Reply-To: %s', $senderEmail);
		$headers[] = sprintf('Return-Path: %s', $senderEmail);
		$headers[] = sprintf('Subject: %s', $subject);
		$headers[] = sprintf('Content-Type: text/plain; charset=utf-8');
		$headers[] = sprintf('X-Mailer: PHP/%s', phpversion());
		$headers[] = sprintf('X-Originating-IP: %s', $clientIp);

		$senderEmail = $this->config->basic->emailAddress;
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

		$arguments = [$recipientEmail, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $senderEmail];
		//throw new \RuntimeException(htmlentities(print_r($arguments, true)));
		call_user_func_array('mail', $arguments);
	}

	private function getFooter()
	{
		return PHP_EOL . PHP_EOL .
			'Thank you and have a nice day,' . PHP_EOL .
			$this->config->basic->serviceName . ' registration bot';
	}
}
