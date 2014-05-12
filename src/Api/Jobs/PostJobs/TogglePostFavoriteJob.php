<?php
class TogglePostFavoriteJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
		$favorite = boolval($this->getArgument(JobArgs::ARG_NEW_STATE));

		if ($favorite)
		{
			UserModel::updateUserScore(Auth::getCurrentUser(), $post, 1);
			UserModel::addToUserFavorites(Auth::getCurrentUser(), $post);
		}
		else
		{
			UserModel::removeFromUserFavorites(Auth::getCurrentUser(), $post);
		}

		return $post;
	}

	public function getRequiredSubArguments()
	{
		return JobArgs::ARG_NEW_STATE;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::FavoritePost,
			Access::getIdentity($this->post->getUploader()));
	}

	public function isAuthenticationRequired()
	{
		return true;
	}
}
