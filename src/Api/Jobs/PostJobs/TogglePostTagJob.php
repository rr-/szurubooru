<?php
class TogglePostTagJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$tagName = $this->getArgument(JobArgs::ARG_TAG_NAME);
		$enable = TextHelper::toBoolean($this->getArgument(JobArgs::ARG_NEW_STATE));
		$post = $this->postRetriever->retrieve();

		$tags = $post->getTags();

		if ($enable)
		{
			$tag = TagModel::tryGetByName($tagName);
			if ($tag === null)
			{
				$tag = TagModel::spawn();
				$tag->setName($tagName);
				TagModel::save($tag);
			}

			$tags []= $tag;
		}
		else
		{
			foreach ($tags as $i => $tag)
				if ($tag->getName() == $tagName)
					unset($tags[$i]);
		}

		$post->setTags($tags);
		PostModel::save($post);
		TagModel::removeUnused();

		if ($enable)
		{
			Logger::log('{user} tagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tagName)]);
		}
		else
		{
			Logger::log('{user} untagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tagName)]);
		}

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::Conjunction(
			JobArgs::ARG_TAG_NAME,
			Jobargs::ARG_NEW_STATE));
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::EditPostTags;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->postRetriever->retrieve()->getUploader());
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
