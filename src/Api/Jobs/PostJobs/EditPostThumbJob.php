<?php
class EditPostThumbJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$file = $this->getArgument(JobArgs::ARG_NEW_THUMB_CONTENT);

		$post->setCustomThumbnailFromPath($file->filePath);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			PostModel::save($post);

		Logger::log('{user} changed thumb of {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_THUMB_CONTENT);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPostThumb
				: Privilege::EditPostThumb,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}
}
