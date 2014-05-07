<?php
class ActivateUserEmailJob extends AbstractJob
{
	const TOKEN = 'token';

	public function execute()
	{
		if (!$this->hasArgument(self::TOKEN))
		{
			$user = UserModel::findByNameOrEmail($this->getArgument(self::USER_NAME));

			if (empty($user->getUnconfirmedEmail()))
			{
				if (!empty($user->getConfirmedEmail()))
					throw new SimpleException('E-mail was already confirmed; activation skipped');
				else
					throw new SimpleException('This user has no e-mail specified; activation cannot proceed');
			}

			self::sendEmail($user);

			return $user;
		}
		else
		{
			$tokenText = $this->getArgument(self::TOKEN);
			$token = TokenModel::findByToken($tokenText);
			TokenModel::checkValidity($token);

			$user = $token->getUser();
			$user->confirmEmail();
			$token->used = true;
			TokenModel::save($token);
			UserModel::save($user);

			Logger::log('{subject} just activated account', [
				'subject' => TextHelper::reprUser($user)]);

			return $user;
		}
	}

	public static function sendEmail($user)
	{
		$regConfig = getConfig()->registration;

		if (!$regConfig->confirmationEmailEnabled)
		{
			$user->confirmEmail();
			return;
		}

		$mail = new Mail();
		$mail->body = $regConfig->confirmationEmailBody;
		$mail->subject = $regConfig->confirmationEmailSubject;
		$mail->senderName = $regConfig->confirmationEmailSenderName;
		$mail->senderEmail = $regConfig->confirmationEmailSenderEmail;
		$mail->recipientEmail = $user->getUnconfirmedEmail();

		return Mailer::sendMailWithTokenLink(
			$user,
			['UserController', 'activationAction'],
			$mail);
	}
}
