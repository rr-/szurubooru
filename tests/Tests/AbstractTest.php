<?php
class AbstractTest
{
	public $assert;
	protected $postMocker;
	protected $tagMocker;
	protected $userMocker;
	protected $commentMocker;
	protected $testSupport;

	public function __construct()
	{
		$this->assert = new Assert();
		$this->testSupport = new TestSupport($this->assert);
		$this->tagMocker = new TagMocker();
		$this->postMocker = new PostMocker($this->tagMocker, $this->testSupport);
		$this->userMocker = new UserMocker();
		$this->commentMocker = new CommentMocker($this->postMocker);
	}

	public function setup()
	{
	}

	public function teardown()
	{
	}

	protected function login($user)
	{
		Auth::setCurrentUser($user);
	}

	protected function grantAccess($privilege)
	{
		Core::getConfig()->privileges->$privilege = 'anonymous';
		Access::initWithoutCache();
	}

	protected function revokeAccess($privilege)
	{
		Core::getConfig()->privileges->$privilege = 'nobody';
		Access::initWithoutCache();
	}
}
