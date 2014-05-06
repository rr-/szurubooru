<?php
class EditPostSourceJob extends AbstractPostJob
{
	const SOURCE = 'source';

	public function isSatisfied()
	{
		return $this->hasArgument(self::SOURCE);
	}

	public function execute()
	{
		$post = $this->post;
		$newSource = $this->getArgument(self::SOURCE);

		$oldSource = $post->source;
		$post->setSource($newSource);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		if ($oldSource != $newSource)
		{
			Logger::log('{user} changed source of {post} to {source}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'source' => $post->source]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostSource
				: Privilege::EditPostSource,
			Access::getIdentity($this->post->getUploader()));
	}
}
