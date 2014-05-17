<?php
class ScorePostJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$score = TextHelper::toInteger($this->getArgument(JobArgs::ARG_NEW_POST_SCORE));

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, $score);

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_POST_SCORE);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ScorePost;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->postRetriever->retrieve()->getUploader());
	}

	public function isAuthenticationRequired()
	{
		return true;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
