<?php
class EditPostContentJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();

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

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::Alternative(
				JobArgs::ARG_NEW_POST_CONTENT,
				JobArgs::ARG_NEW_POST_CONTENT_URL));
	}

	public function getRequiredMainPrivilege()
	{
		return $this->getContext() == self::CONTEXT_BATCH_ADD
			? Privilege::AddPostContent
			: Privilege::EditPostContent;
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
