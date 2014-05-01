<?php
class AddCommentJob extends AbstractJob
{
	public function execute($arguments)
	{
		$post = PostModel::findById($arguments['post-id']);
		$user = Auth::getCurrentUser();
		$text = CommentModel::validateText($arguments['text']);

		$comment = CommentModel::spawn();
		$comment->setCommenter($user);
		$comment->setPost($post);
		$comment->commentDate = time();
		$comment->text = $text;

		CommentModel::save($comment);
		LogHelper::log('{user} commented on {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($comment->getPost()->id)]);

		return $comment;
	}

	public function requiresPrivilege()
	{
		return Privilege::AddComment;
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
