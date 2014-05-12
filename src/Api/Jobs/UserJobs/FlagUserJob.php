<?php
class FlagUserJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		$key = TextHelper::reprUser($user);

		$flagged = SessionHelper::get('flagged', []);
		if (in_array($key, $flagged))
			throw new SimpleException('You already flagged this user');
		$flagged []= $key;
		SessionHelper::set('flagged', $flagged);

		Logger::log('{user} flagged {subject} for moderator attention', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);

		return $user;
	}

	public function getRequiredArguments()
	{
		return $this->userRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::FlagUser,
			Access::getIdentity($this->userRetriever->retrieve()));
	}
}
