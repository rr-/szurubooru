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

		LogHelper::log('{user} flagged {post} for moderator attention', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::FlagPost,
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
