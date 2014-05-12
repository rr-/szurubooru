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

	public function testSubPrivilegesOnlySub()
	{
		getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Access::init();

		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'own'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'all'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testSubPrivilegesSubAndGeneralNormalOrder()
	{
		getConfig()->privileges->{'listPosts.own'} = 'power-user';
		getConfig()->privileges->{'listPosts'} = 'admin';
		Access::init();
		$this->testSubPrivilegesSubAndGeneral();
	}

	public function testSubPrivilegesSubAndGeneralReverseOrder()
	{
		getConfig()->privileges->{'listPosts'} = 'admin';
		getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Access::init();
		$this->testSubPrivilegesSubAndGeneral();
	}

	protected function testSubPrivilegesSubAndGeneral()
	{
		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'own'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts, 'baka'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Admin));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'own'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'all'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testSubPrivilegesMultipleSubAndGeneralNormalOrder()
	{
		getConfig()->privileges->{'listPosts.own'} = 'power-user';
		getConfig()->privileges->{'listPosts.all'} = 'admin';
		getConfig()->privileges->{'listPosts'} = 'nobody';
		Access::init();
		$this->testSubPrivilegesMultipleSubAndGeneral();
	}

	public function testSubPrivilegesMultipleSubAndGeneralReverseOrder()
	{
		getConfig()->privileges->{'listPosts'} = 'nobody';
		getConfig()->privileges->{'listPosts.own'} = 'power-user';
		getConfig()->privileges->{'listPosts.all'} = 'admin';
		Access::init();
		$this->testSubPrivilegesMultipleSubAndGeneral();
	}

	protected function testSubPrivilegesMultipleSubAndGeneral()
	{
		$user = $this->mockUser();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'own'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts, 'all'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));

		$user->setAccessRank(new AccessRank(AccessRank::Admin));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'own'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'all'), $user));
		$this->assert->isFalse(Access::check(new Privilege(Privilege::ListPosts), $user));
	}
}
