<?php
class AddCommentJob extends AbstractJob
{
	public function execute()
	{
		$user = Auth::getCurrentUser();
		$post = PostModel::findById($this->getArgument(self::POST_ID));
		$text = $this->getArgument(self::TEXT);

		$comment = CommentModel::spawn();
		$comment->setCommenter($user);
		$comment->setPost($post);
		$comment->setDateTime(time());
		$comment->setText($text);

		CommentModel::save($comment);
		Logger::log('{user} commented on {post}', [
			'user' => TextHelper::reprUser($user),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::AddComment);
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
