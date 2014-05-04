<?php
class EditPostSourceJob extends AbstractPostEditJob
{
	const SOURCE = 'source';

	public function execute()
	{
		$post = $this->post;
		$newSource = $this->getArgument(self::SOURCE);

		$oldSource = $post->source;
		$post->setSource($newSource);

		if (!$this->skipSaving)
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
			Privilege::EditPostSource,
			Access::getIdentity($this->post->getUploader()));
	}
}
