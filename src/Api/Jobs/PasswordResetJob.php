<?php
class PasswordResetJob extends AbstractJob
{
	public function execute()
	{
		if (!$this->hasArgument(JobArgs::ARG_TOKEN))
		{
			if ($this->hasArgument(JobArgs::ARG_USER_NAME))
				$user = UserModel::getByNameOrEmail($this->getArgument(JobArgs::ARG_USER_NAME));
			elseif ($this->hasArgument(JobArgs::ARG_USER_EMAIL))
				$user = UserModel::getByNameOrEmail($this->getArgument(JobArgs::ARG_USER_EMAIL));
			else
				$user = $this->getArgument(JobArgs::ARG_USER_ENTITTY);

			if (empty($user->getConfirmedEmail()))
				throw new SimpleException('This user has no e-mail confirmed; password reset cannot proceed');

			UserModel::sendPasswordResetEmail($user);

			return $user;
		}
		else
		{
			$tokenText = $this->getArgument(JobArgs::ARG_TOKEN);
			$token = TokenModel::getByToken($tokenText);
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

	public function getRequiredArguments()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_USER_NAME,
			JobArgs::ARG_USER_EMAIL,
			JobArgs::ARG_USER_ENTITY,
			JobArgs::ARG_TOKEN);
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
