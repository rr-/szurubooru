<?php
class EditPostRelationsJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$relatedPostIds = $this->getArgument(JobArgs::ARG_NEW_RELATED_POST_IDS);

		if (!is_array($relatedPostIds))
			throw new SimpleException('Expected array');

		$relatedPosts = PostModel::getAllByIds($relatedPostIds);

		$oldRelatedIds = array_map(function($post) { return $post->getId(); }, $post->getRelations());
		$post->setRelations($relatedPosts);
		$newRelatedIds = array_map(function($post) { return $post->getId(); }, $post->getRelations());

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		foreach (array_diff($oldRelatedIds, $newRelatedIds) as $post2id)
		{
			Logger::log('{user} removed relation between {post} and {post2}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'post2' => TextHelper::reprPost($post2id)]);
		}

		foreach (array_diff($newRelatedIds, $oldRelatedIds) as $post2id)
		{
			Logger::log('{user} added relation between {post} and {post2}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'post2' => TextHelper::reprPost($post2id)]);
		}

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_RELATED_POST_IDS);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostRelations
				: Privilege::EditPostRelations,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}
}
