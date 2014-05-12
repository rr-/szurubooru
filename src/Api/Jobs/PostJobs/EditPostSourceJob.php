<?php
class EditPostSourceJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
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

	public function getRequiredSubArguments()
	{
		return JobArgs::ARG_NEW_SOURCE;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostSource
				: Privilege::EditPostSource,
			Access::getIdentity($this->post->getUploader()));
	}
}
