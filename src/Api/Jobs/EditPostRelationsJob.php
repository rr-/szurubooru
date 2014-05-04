<?php
class EditPostRelationsJob extends AbstractPostEditJob
{
	const RELATED_POST_IDS = 'related-post-ids';

	public function execute()
	{
		$post = $this->post;
		$relations = $this->getArgument(self::RELATED_POST_IDS);

		$oldRelatedIds = array_map(function($post) { return $post->id; }, $post->getRelations());
		$post->setRelationsFromText($relations);
		$newRelatedIds = array_map(function($post) { return $post->id; }, $post->getRelations());

		if (!$this->skipSaving)
			PostModel::save($post);

		foreach (array_diff($oldRelatedIds, $newRelatedIds) as $post2id)
		{
			LogHelper::log('{user} removed relation between {post} and {post2}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'post2' => TextHelper::reprPost($post2id)]);
		}

		foreach (array_diff($newRelatedIds, $oldRelatedIds) as $post2id)
		{
			LogHelper::log('{user} added relation between {post} and {post2}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'post2' => TextHelper::reprPost($post2id)]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostRelations,
			Access::getIdentity($this->post->getUploader()));
	}
}
