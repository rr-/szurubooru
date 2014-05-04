<?php
class EditPostSafetyJob extends AbstractPostEditJob
{
	const SAFETY = 'safety';

	public function execute()
	{
		$post = $this->post;
		$newSafety = $this->getArgument(self::SAFETY);

		$oldSafety = $post->safety;
		$post->setSafety($newSafety);

		if (!$this->skipSaving)
			PostModel::save($post);

		if ($oldSafety != $newSafety)
		{
			LogHelper::log('{user} changed safety of {post} to {safety}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'safety' => PostSafety::toString($post->safety)]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostSafety,
			Access::getIdentity($this->post->getUploader()));
	}
}
