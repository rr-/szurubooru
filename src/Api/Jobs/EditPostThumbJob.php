<?php
class EditPostThumbJob extends AbstractPostEditJob
{
	const THUMB_CONTENT = 'thumb-content';

	public function execute()
	{
		$post = $this->post;
		$file = $this->getArgument(self::THUMB_CONTENT);

		$post->setCustomThumbnailFromPath($file->filePath);

		if (!$this->skipSaving)
			PostModel::save($post);

		LogHelper::log('{user} changed thumb of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostThumb,
			Access::getIdentity($this->post->getUploader()));
	}
}
