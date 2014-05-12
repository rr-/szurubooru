<?php
class TogglePostFavoriteJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
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

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_STATE);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::FavoritePost,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}

	public function isAuthenticationRequired()
	{
		return true;
	}
}
