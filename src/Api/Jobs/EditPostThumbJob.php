<?php
class EditPostThumbJob extends AbstractPostJob
{
	const THUMB_CONTENT = 'thumb-content';

	public function isSatisfied()
	{
		return $this->hasArgument(self::THUMB_CONTENT);
	}

	public function execute()
	{
		$post = $this->post;
		$file = $this->getArgument(self::THUMB_CONTENT);

		$post->setCustomThumbnailFromPath($file->filePath);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		Logger::log('{user} changed thumb of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostThumb
				: Privilege::EditPostThumb,
			Access::getIdentity($this->post->getUploader()));
	}
}
