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

		$arguments = $this->getArguments();
		$arguments[JobArgs::ARG_POST_ENTITY] = $post;

		Logger::bufferChanges();
		foreach ($this->getSubJobs() as $subJob)
		{
			$subJob->setContext(self::CONTEXT_BATCH_EDIT);

			try
			{
				Api::run($subJob, $arguments);
			}
			catch (ApiJobUnsatisfiedException $e)
			{
			}
		}

		PostModel::save($post);
		Logger::flush();

		return $post;
	}

	public function getRequiredArguments()
	{
		return $this->postRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::EditPost,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}
}
