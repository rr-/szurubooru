<?php
class PasswordResetJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		if (!$this->hasArgument(JobArgs::ARG_TOKEN))
		{
			$user = $this->userRetriever->retrieve();

			if (empty($user->getConfirmedEmail()))
				throw new SimpleException('This user has no e-mail confirmed; password reset cannot proceed');

			self::sendEmail($user);

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
			$token->setUsed(true);
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
			$this->userRetriever->getRequiredArguments(),
			JobArgs::ARG_TOKEN);
	}

	public function getRequiredMainPrivilege()
	{
		return null;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}

	public function isAvailableToPublic()
	{
		return false;
	}

	public static function sendEmail($user)
	{
		$regConfig = Core::getConfig()->registration;

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
