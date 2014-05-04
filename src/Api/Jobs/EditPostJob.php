<?php
class EditPostJob extends AbstractPostEditJob
{
	public function execute()
	{
		$post = $this->post;

		LogHelper::bufferChanges();

		$subJobs =
		[
			new EditPostSafetyJob(),
			new EditPostTagsJob(),
			new EditPostSourceJob(),
			new EditPostRelationsJob(),
			new EditPostContentJob(),
			new EditPostUrlJob(),
			new EditPostThumbJob(),
		];

		foreach ($subJobs as $subJob)
		{
			if ($this->skipSaving)
				$subJob->skipSaving();

			$args = $this->getArguments();
			$args[self::POST_ENTITY] = $post;
			try
			{
				Api::run($subJob, $args);
			}
			catch (ApiMissingArgumentException $e)
			{
			}
		}

		if (!$this->skipSaving)
			PostModel::save($post);

		LogHelper::flush();
		return $post;
	}
}
