<?php
class EditPostContentJob extends AbstractPostEditJob
{
	const CONTENT = 'content';

	public function execute()
	{
		$file = $this->getArgument(self::CONTENT);

		$this->post->setContentFromPath($file->filePath, $file->fileName);

		PostModel::save($this->post);
		LogHelper::log('{user} changed contents of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($this->post)]);

		return $this->post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::EditPostFile,
			Access::getIdentity($this->post->getUploader())
		];
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
