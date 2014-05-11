<?php
class EditPostContentJob extends AbstractPostJob
{
	public function isSatisfied()
	{
		return $this->hasArgument(JobArgs::ARG_NEW_POST_CONTENT)
			or $this->hasArgument(JobArgs::ARG_NEW_POST_CONTENT_URL);
	}

	public function execute()
	{
		$post = $this->post;

		if ($this->hasArgument(JobArgs::ARG_NEW_POST_CONTENT_URL))
		{
			$url = $this->getArgument(JobArgs::ARG_NEW_POST_CONTENT_URL);
			$post->setContentFromUrl($url);
		}
		else
		{
			$file = $this->getArgument(JobArgs::ARG_NEW_POST_CONTENT);
			$post->setContentFromPath($file->filePath, $file->fileName);
		}

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		Logger::log('{user} changed contents of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostContent
				: Privilege::EditPostContent,
			Access::getIdentity($this->post->getUploader()));
	}
}
