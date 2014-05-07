<?php
class PasswordResetJob extends AbstractJob
{
	const TOKEN = 'token';
	const NEW_PASSWORD = 'new-password';

	public function execute()
	{
		if (!$this->hasArgument(self::TOKEN))
		{
			$user = UserModel::findByNameOrEmail($this->getArgument(self::USER_NAME));

			if (empty($user->getConfirmedEmail()))
				throw new SimpleException('This user has no e-mail confirmed; password reset cannot proceed');

			UserModel::sendPasswordResetEmail($user);

			return $user;
		}
		else
		{
			$tokenText = $this->getArgument(self::TOKEN);
			$token = TokenModel::findByToken($tokenText);
			TokenModel::checkValidity($token);

			$alphabet = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
			$newPassword = join('', array_map(function($x) use ($alphabet)
			{
				return $alphabet[$x];
			}, array_rand($alphabet, 8)));

			$user = $token->getUser();
			$user->setPassword($newPassword);
			$token->used = true;
			TokenModel::save($token);
			UserModel::save($user);

			Logger::log('{subject} just reset password', [
				'subject' => TextHelper::reprUser($user)]);

			$x = new StdClass;
			$x->user = $user;
			$x->newPassword = $newPassword;
			return $x;
		}
	}

	public static function sendEmail($user)
	{
		$regConfig = getConfig()->registration;

		$mail = new Mail();
		$mail->body = $regConfig->passwordResetEmailBody;
		$mail->subject = $regConfig->passwordResetEmailSubject;
		$mail->senderName = $regConfig->passwordResetEmailSenderName;
		$mail->senderEmail = $regConfig->passwordResetEmailSenderEmail;
		$mail->recipientEmail = $user->getConfirmedEmail();

		return Mailer::sendMailWithTokenLink(
			$user,
			['UserController', 'passwordResetAction'],
			$mail);
	}
}
