<?php
class AddPostJob extends AbstractJob
{
	const ANONYMOUS = 'anonymous';

	public function execute()
	{
		$post = PostModel::spawn();
		Logger::bufferChanges();

		//basic stuff
		$anonymous = $this->getArgument(self::ANONYMOUS);
		if (Auth::isLoggedIn() and !$anonymous)
			$post->setUploader(Auth::getCurrentUser());

		//store the post to get the ID in the logs
		PostModel::forgeId($post);

		//do the edits
		//warning: it uses internally the same privileges as post editing
		$arguments = $this->getArguments();
		$arguments[EditPostJob::POST_ENTITY] = $post;
		Api::run((new EditPostJob)->skipSaving(), $arguments);

		//save to db
		PostModel::save($post);

		//clean edit log
		Logger::setBuffer([]);

		//log
		Logger::log('{user} added {post} (tags: {tags}, safety: {safety}, source: {source})', [
			'user' => ($anonymous and !getConfig()->misc->logAnonymousUploads)
				? TextHelper::reprUser(UserModel::getAnonymousName())
				: TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post),
			'tags' => TextHelper::reprTags($post->getTags()),
			'safety' => $post->getSafety()->toString(),
			'source' => $post->source]);

		//finish
		Logger::flush();

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::UploadPost);
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForUploading;
	}
}
