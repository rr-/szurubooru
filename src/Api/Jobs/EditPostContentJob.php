<?php
class EditPostContentJob extends AbstractPostEditJob
{
	const POST_CONTENT = 'post-content';
	const POST_CONTENT_URL = 'post-content-url';

	public function execute()
	{
		$post = $this->post;

		if ($this->hasArgument(self::POST_CONTENT_URL))
		{
			$url = $this->getArgument(self::POST_CONTENT_URL);
			$post->setContentFromUrl($url);
		}
		else
		{
			$file = $this->getArgument(self::POST_CONTENT);
			$post->setContentFromPath($file->filePath, $file->fileName);
		}

		if (!$this->skipSaving)
			PostModel::save($post);

		Logger::log('{user} changed contents of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostContent,
			Access::getIdentity($this->post->getUploader()));
	}
}
