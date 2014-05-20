<?php
class AddPostJob extends AbstractJob
{
	public function __construct()
	{
		$this->addSubJob(new EditPostSafetyJob());
		$this->addSubJob(new EditPostTagsJob());
		$this->addSubJob(new EditPostSourceJob());
		$this->addSubJob(new EditPostRelationsJob());
		$this->addSubJob(new EditPostContentJob());
		$this->addSubJob(new EditPostThumbnailJob());
	}

	public function execute()
	{
		$post = PostModel::spawn();

		$anonymous = false;
		if ($this->hasArgument(JobArgs::ARG_ANONYMOUS))
			$anonymous = TextHelper::toBoolean($this->getArgument(JobArgs::ARG_ANONYMOUS));

		if (Auth::isLoggedIn() and !$anonymous)
			$post->setUploader(Auth::getCurrentUser());

		PostModel::forgeId($post);

		$arguments = $this->getArguments();
		$arguments[JobArgs::ARG_POST_ENTITY] = $post;

		Logger::bufferChanges();
		foreach ($this->getSubJobs() as $subJob)
		{
			$subJob->setContext(AbstractJob::CONTEXT_BATCH_ADD);
			try
			{
				Api::run($subJob, $arguments);
			}
			catch (ApiJobUnsatisfiedException $e)
			{
			}
			finally
			{
				Logger::discardBuffer();
			}
		}

		//save the post to db if everything went okay
		PostModel::save($post);

		Logger::log('{user} added {post} (tags: {tags}, safety: {safety}, source: {source})', [
			'user' => ($anonymous and !Core::getConfig()->misc->logAnonymousUploads)
				? TextHelper::reprUser(UserModel::getAnonymousName())
				: TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post),
			'tags' => TextHelper::reprTags($post->getTags()),
			'safety' => $post->getSafety()->toString(),
			'source' => $post->getSource()]);

		Logger::flush();

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Optional(JobArgs::ARG_ANONYMOUS);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::AddPost;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return Core::getConfig()->registration->needEmailForUploading;
	}
}
