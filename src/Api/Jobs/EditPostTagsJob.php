<?php
class EditPostTagsJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
		$tags = $this->getArgument(self::TAG_NAMES);

		$oldTags = array_map(function($tag) { return $tag->name; }, $post->getTags());
		$post->setTagsFromText($tags);
		$newTags = array_map(function($tag) { return $tag->name; }, $post->getTags());

		PostModel::save($post);

		foreach (array_diff($oldTags, $newTags) as $tag)
		{
			LogHelper::log('{user} untagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}

		foreach (array_diff($newTags, $oldTags) as $tag)
		{
			LogHelper::log('{user} tagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::EditPostTags,
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
