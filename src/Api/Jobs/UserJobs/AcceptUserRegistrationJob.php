<?php
class AcceptUserRegistrationJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->getRequiredArguments();

		$user->setStaffConfirmed(true);
		UserModel::save($user);

		Logger::log('{user} confirmed {subject}\'s account', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);
	}

	public function getRequiredArguments()
	{
		return $this->userRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::AcceptUserRegistration);
	}
}
