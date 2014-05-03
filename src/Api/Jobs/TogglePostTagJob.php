<?php
class TogglePostTagJob extends AbstractPostJob
{
	public function execute()
	{
		$tagName = $this->getArgument(self::TAG_NAME);
		$enable = boolval($this->getArgument(self::STATE));
		$post = $this->post;

		$tags = $post->getTags();

		if ($enable)
		{
			$tag = TagModel::findByName($tagName, false);
			if ($tag === null)
			{
				$tag = TagModel::spawn();
				$tag->name = $tagName;
				TagModel::save($tag);
			}

			$tags []= $tag;
		}
		else
		{
			foreach ($tags as $i => $tag)
				if ($tag->name == $tagName)
					unset($tags[$i]);
		}

		$post->setTags($tags);
		PostModel::save($post);
		TagModel::removeUnused();

		if ($enable)
		{
			LogHelper::log('{user} tagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}
		else
		{
			LogHelper::log('{user} untagged {post} with {tag}', [
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
