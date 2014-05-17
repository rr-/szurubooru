<?php
class ActivateUserEmailJob extends AbstractJob
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
			$tokenText = $this->getArgument(JobArgs::ARG_TOKEN);
			$token = TokenModel::getByToken($tokenText);
			TokenModel::checkValidity($token);

			$user = $token->getUser();
			$user->confirmEmail();
			$token->setUsed(true);
			TokenModel::save($token);
			UserModel::save($user);

			Logger::log('{subject} just activated account', [
				'subject' => TextHelper::reprUser($user)]);

			return $user;
		}
	}

	public function getRequiredArguments()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_TOKEN,
			$this->userRetriever->getRequiredArguments());
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

	public static function sendEmail($user)
	{
		$regConfig = Core::getConfig()->registration;

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
