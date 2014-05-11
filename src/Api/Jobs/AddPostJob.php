<?php
class AddPostJob extends AbstractJob
{
	public function execute()
	{
		$post = PostModel::spawn();

		$anonymous = $this->hasArgument(JobArgs::ARG_ANONYMOUS)
			and $this->getArgument(JobArgs::ARG_ANONYMOUS);
		if (Auth::isLoggedIn() and !$anonymous)
			$post->setUploader(Auth::getCurrentUser());

		PostModel::forgeId($post);

		$arguments = $this->getArguments();
		$arguments[JobArgs::ARG_POST_ENTITY] = $post;

		Logger::bufferChanges();
		try
		{
			$job = new EditPostJob();
			$job->setContext(AbstractJob::CONTEXT_BATCH_ADD);
			Api::run($job, $arguments);
		}
		finally
		{
			Logger::discardBuffer();
		}

		//save the post to db if everything went okay
		PostModel::save($post);

		Logger::log('{user} added {post} (tags: {tags}, safety: {safety}, source: {source})', [
			'user' => ($anonymous and !getConfig()->misc->logAnonymousUploads)
				? TextHelper::reprUser(UserModel::getAnonymousName())
				: TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post),
			'tags' => TextHelper::reprTags($post->getTags()),
			'safety' => $post->getSafety()->toString(),
			'source' => $post->getSource()]);

		Logger::flush();

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::AddPost);
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForUploading;
	}
}
