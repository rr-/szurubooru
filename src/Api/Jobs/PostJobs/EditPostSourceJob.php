<?php
class EditPostSourceJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$newSource = $this->getArgument(JobArgs::ARG_NEW_SOURCE);

		$oldSource = $post->getSource();
		$post->setSource($newSource);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		if ($oldSource != $newSource)
		{
			Logger::log('{user} changed source of {post} to {source}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'source' => $post->getSource()]);
		}

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_SOURCE);
	}

	public function getRequiredMainPrivilege()
	{
		return $this->getContext() == self::CONTEXT_BATCH_ADD
			? Privilege::AddPostSource
			: Privilege::EditPostSource;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->postRetriever->retrieve()->getUploader());
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
