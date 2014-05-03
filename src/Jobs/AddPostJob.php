<?php
class AddPostJob extends AbstractJob
{
	const ANONYMOUS = 'anonymous';

	public function execute()
	{
		$post = PostModel::spawn();
		LogHelper::bufferChanges();

		//basic stuff
		$anonymous = $this->getArgument(self::ANONYMOUS);
		if (Auth::isLoggedIn() and !$anonymous)
			$post->setUploader(Auth::getCurrentUser());

		//store the post to get the ID in the logs
		PostModel::forgeId($post);

		//do the edits
		//warning: each handler runs uses the same privileges as post editing
		$subJobs =
		[
			new EditPostSafetyJob(),
			new EditPostTagsJob(),
			new EditPostSourceJob(),
			new EditPostRelationsJob(),
			new EditPostContentJob(),
			new EditPostUrlJob(),
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

		//load the post after edits
		$post = PostModel::findById($post->id);

		// basically means that user didn't specify file nor url
		//todo:
		//- move this to PostEntity::isValid()
		//- create IValidatable interface
		//- enforce entity validity upon calling save() in models
		if (empty($post->type))
			throw new SimpleException('No post type detected; upload faled');

		//clean edit log
		LogHelper::setBuffer([]);

		//log
		LogHelper::log('{user} added {post} (tags: {tags}, safety: {safety}, source: {source})', [
			'user' => ($anonymous and !getConfig()->misc->logAnonymousUploads)
				? TextHelper::reprUser(UserModel::getAnonymousName())
				: TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post),
			'tags' => TextHelper::reprTags($post->getTags()),
			'safety' => PostSafety::toString($post->safety),
			'source' => $post->source]);

		//finish
		LogHelper::flush();
		PostModel::save($post);

		return $post;
	}

	public function requiresPrivilege()
	{
		return Privilege::UploadPost;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForUploading;
	}
}
