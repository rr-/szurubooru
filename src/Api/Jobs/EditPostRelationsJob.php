<?php
class EditPostRelationsJob extends AbstractPostJob
{
	const RELATED_POST_IDS = 'related-post-ids';

	public function isSatisfied()
	{
		return $this->hasArgument(self::RELATED_POST_IDS);
	}

	public function execute()
	{
		$post = $this->post;
		$relations = $this->getArgument(self::RELATED_POST_IDS);

		$oldRelatedIds = array_map(function($post) { return $post->getId(); }, $post->getRelations());
		$post->setRelationsFromText($relations);
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

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostRelations
				: Privilege::EditPostRelations,
			Access::getIdentity($this->post->getUploader()));
	}
}
