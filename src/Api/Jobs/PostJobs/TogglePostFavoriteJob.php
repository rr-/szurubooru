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
		$favorite = TextHelper::toBoolean($this->getArgument(JobArgs::ARG_NEW_STATE));

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

	public function getRequiredMainPrivilege()
	{
		return Privilege::FavoritePost;
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
