<?php
class AccessTest extends AbstractTest
{
	public function testDefaultPrivilege()
	{
		//by default, all privileges are set to false
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts)));
	}

	public function testAccessRanks1()
	{
		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::Admin));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Moderator));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Nobody));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testAccessRanks2()
	{
		getConfig()->privileges->listPosts = 'power-user';
		Access::init();

		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::Admin));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Moderator));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Nobody));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testSubPrivileges1()
	{
		getConfig()->privileges->{'listPosts.dummy'} = 'power-user';
		Access::init();

		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'dummy'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'baka'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testSubPrivileges2a()
	{
		getConfig()->privileges->{'listPosts.dummy'} = 'power-user';
		getConfig()->privileges->{'listPosts'} = 'admin';
		Access::init();
		$this->testSubPrivileges2();
	}

	public function testSubPrivileges2b()
	{
		getConfig()->privileges->{'listPosts'} = 'admin';
		getConfig()->privileges->{'listPosts.dummy'} = 'power-user';
		Access::init();
		$this->testSubPrivileges2();
	}

	protected function testSubPrivileges2()
	{
		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'dummy'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts, 'baka'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Admin));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'dummy'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'baka'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testSubPrivileges3a()
	{
		getConfig()->privileges->{'listPosts.dummy'} = 'power-user';
		getConfig()->privileges->{'listPosts.baka'} = 'admin';
		getConfig()->privileges->{'listPosts'} = 'nobody';
		Access::init();
		$this->testSubPrivileges3();
	}

	public function testSubPrivileges3b()
	{
		getConfig()->privileges->{'listPosts'} = 'nobody';
		getConfig()->privileges->{'listPosts.dummy'} = 'power-user';
		getConfig()->privileges->{'listPosts.baka'} = 'admin';
		Access::init();
		$this->testSubPrivileges3();
	}

	protected function testSubPrivileges3()
	{
		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'dummy'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts, 'baka'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Admin));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'dummy'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'baka'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));
	}
}
