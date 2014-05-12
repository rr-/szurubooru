<?php
class EditPostSafetyJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$newSafety = new PostSafety($this->getArgument(JobArgs::ARG_NEW_SAFETY));

		$oldSafety = $post->getSafety();
		$post->setSafety($newSafety);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		if ($oldSafety != $newSafety)
		{
			Logger::log('{user} changed safety of {post} to {safety}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'safety' => $post->getSafety()->toString()]);
		}

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_SAFETY);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostSafety
				: Privilege::EditPostSafety,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}
}
