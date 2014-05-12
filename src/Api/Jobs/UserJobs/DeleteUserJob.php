<?php
class DeleteUserJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();

		$name = $user->getName();
		UserModel::remove($user);

		Logger::log('{user} removed {subject}\'s account', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($name)]);
	}

	public function getRequiredArguments()
	{
		return $this->userRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::DeleteUser,
			Access::getIdentity($this->userRetriever->retrieve()));
	}
}
