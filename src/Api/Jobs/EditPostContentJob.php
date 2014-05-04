<?php
class EditPostContentJob extends AbstractPostJob
{
	const POST_CONTENT = 'post-content';

	public function execute()
	{
		$post = $this->post;
		$file = $this->getArgument(self::POST_CONTENT);

		$post->setContentFromPath($file->filePath, $file->fileName);

		PostModel::save($post);
		LogHelper::log('{user} changed contents of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::EditPostFile,
			Access::getIdentity($this->post->getUploader())
		];
	}
}
