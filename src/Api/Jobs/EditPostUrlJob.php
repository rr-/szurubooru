<?php
class EditPostUrlJob extends AbstractPostJob
{
	const POST_CONTENT_URL = 'post-content-url';

	public function execute()
	{
		$post = $this->post;
		$url = $this->getArgument(self::POST_CONTENT_URL);

		$post->setContentFromUrl($url);

		PostModel::save($post);

		LogHelper::log('{user} changed contents of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostFile,
			Access::getIdentity($this->post->getUploader()));
	}
}
