<?php
class EditPostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

		Logger::bufferChanges();

		$subJobs =
		[
			new EditPostSafetyJob(),
			new EditPostTagsJob(),
			new EditPostSourceJob(),
			new EditPostRelationsJob(),
			new EditPostContentJob(),
			new EditPostThumbJob(),
		];

		foreach ($subJobs as $subJob)
		{
			$subJob->setContext($this->getContext() == self::CONTEXT_BATCH_ADD
				? self::CONTEXT_BATCH_ADD
				: self::CONTEXT_BATCH_EDIT);

			$args = $this->getArguments();
			$args[self::POST_ENTITY] = $post;
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

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::AddPost
				: Privilege::EditPost,
			Access::getIdentity($this->post->getUploader()));
	}
}
