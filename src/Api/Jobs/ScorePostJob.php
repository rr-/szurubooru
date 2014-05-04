<?php
class ScorePostJob extends AbstractPostJob
{
	const SCORE = 'score';

	public function execute()
	{
		$post = $this->post;
		$score = intval($this->getArgument(self::SCORE));

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, $score);
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::ScorePost,
			Access::getIdentity($this->post->getUploader()));
	}
}
