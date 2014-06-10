<?php
class EditPostTagsJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieveForEditing();
		$tagNames = $this->getArgument(JobArgs::ARG_NEW_TAG_NAMES);

		if (!is_array($tagNames))
			throw new SimpleException('Expected array');

		$tags = TagModel::spawnFromNames($tagNames);

		$oldTags = array_map(function($tag) { return $tag->getName(); }, $post->getTags());
		$post->setTags($tags);
		$newTags = array_map(function($tag) { return $tag->getName(); }, $post->getTags());

		if ($this->getContext() == self::CONTEXT_NORMAL)
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

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArgumentsForEditing(),
			JobArgs::ARG_NEW_TAG_NAMES);
	}

	public function getRequiredMainPrivilege()
	{
		return $this->getContext() == self::CONTEXT_BATCH_ADD
			? Privilege::AddPostTags
			: Privilege::EditPostTags;
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
