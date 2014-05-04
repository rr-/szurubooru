<?php
class EditPostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

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
			$args = $this->getArguments();
			$args[self::POST_ID] = $post->id;
			try
			{
				Api::run($subJob, $args);
			}
			catch (ApiMissingArgumentException $e)
			{
			}
		}

		return $post;
	}
}
