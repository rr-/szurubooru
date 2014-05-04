<?php
class EditPostTagsJob extends AbstractPostEditJob
{
	public function execute()
	{
		$post = $this->post;
		$tags = $this->getArgument(self::TAG_NAMES);

		$oldTags = array_map(function($tag) { return $tag->name; }, $post->getTags());
		$post->setTagsFromText($tags);
		$newTags = array_map(function($tag) { return $tag->name; }, $post->getTags());

		if (!$this->skipSaving)
		{
			PostModel::save($post);
			TagModel::removeUnused();
		}

		foreach (array_diff($oldTags, $newTags) as $tag)
		{
			Logger::log('{user} untagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}

		foreach (array_diff($newTags, $oldTags) as $tag)
		{
			Logger::log('{user} tagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostTags,
			Access::getIdentity($this->post->getUploader()));
	}
}
