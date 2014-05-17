<?php
class EditPostJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
		$this->addSubJob(new EditPostSafetyJob());
		$this->addSubJob(new EditPostTagsJob());
		$this->addSubJob(new EditPostSourceJob());
		$this->addSubJob(new EditPostRelationsJob());
		$this->addSubJob(new EditPostContentJob());
		$this->addSubJob(new EditPostThumbJob());
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();

		Logger::bufferChanges();

		foreach ($this->getSubJobs() as $subJob)
		{
			$subJob->setContext($this->getContext() == self::CONTEXT_BATCH_ADD
				? self::CONTEXT_BATCH_ADD
				: self::CONTEXT_BATCH_EDIT);

			$args = $this->getArguments();
			$args[JobArgs::ARG_POST_ENTITY] = $post;
			try
			{
				Api::run($subJob, $args);
			}
			catch (ApiJobUnsatisfiedException $e)
			{
			}
		}

		if ($this->getContext() == AbstractJob::CONTEXT_NORMAL)
		{
			PostModel::save($post);
			Logger::flush();
		}

		return $post;
	}

	public function getRequiredArguments()
	{
		return $this->postRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPost
				: Privilege::EditPost,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}
}
