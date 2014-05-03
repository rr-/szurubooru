<?php
class TogglePostFavoriteJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
		$favorite = boolval($this->getArgument(self::STATE));

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

	public function requiresPrivilege()
	{
		return
		[
			Privilege::FavoritePost,
			Access::getIdentity($this->post->getUploader())
		];
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
