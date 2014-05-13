<?php
class AbstractTest
{
	public $assert;

	public function __construct()
	{
		$this->assert = new Assert();
	}

	public function setup()
	{
	}

	public function teardown()
	{
	}

	protected function mockUser()
	{
		$user = UserModel::spawn();
		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$user->setName('dummy'.uniqid());
		$user->setPassword('sekai');
		return UserModel::save($user);
	}

	protected function mockTag()
	{
		$tag = TagModel::spawn();
		$tag->setName(uniqid());
		return TagModel::save($tag);
	}

	protected function mockPost($owner)
	{
		$post = PostModel::spawn();
		$post->setUploader($owner);
		$post->setType(new PostType(PostType::Image));
		$post->setTags([$this->mockTag(), $this->mockTag()]);
		copy($this->getPath('image.jpg'), $post->getFullPath());
		return PostModel::save($post);
	}

	protected function login($user)
	{
		Auth::setCurrentUser($user);
	}

	protected function mockComment($owner)
	{
		$post = $this->mockPost($owner);
		$comment = CommentModel::spawn();
		$comment->setPost($post);
		$comment->setCommenter($owner);
		$comment->setText('test test');
		return CommentModel::save($comment);
	}

	protected function grantAccess($privilege)
	{
		getConfig()->privileges->$privilege = 'anonymous';
		Access::init();
	}

	protected function revokeAccess($privilege)
	{
		getConfig()->privileges->$privilege = 'nobody';
		Access::init();
	}

	protected function getPath($name)
	{
		return getConfig()->rootDir . DS . 'tests' . DS . 'TestFiles' . DS . $name;
	}
}
