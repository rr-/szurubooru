<?php
class FlagPostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
		$key = TextHelper::reprPost($post);

		$flagged = SessionHelper::get('flagged', []);
		if (in_array($key, $flagged))
			throw new SimpleException('You already flagged this post');
		$flagged []= $key;
		SessionHelper::set('flagged', $flagged);

		Logger::log('{user} flagged {post} for moderator attention', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function getRequiredSubArguments()
	{
		return null;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::FlagPost,
			Access::getIdentity($this->post->getUploader()));
	}
}
