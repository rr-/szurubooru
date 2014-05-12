<?php
class ScorePostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
		$score = intval($this->getArgument(JobArgs::ARG_NEW_POST_SCORE));

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, $score);

		return $post;
	}

	public function getRequiredSubArguments()
	{
		return JobArgs::ARG_NEW_POST_SCORE;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::ScorePost,
			Access::getIdentity($this->post->getUploader()));
	}

	public function isAuthenticationRequired()
	{
		return true;
	}
}
