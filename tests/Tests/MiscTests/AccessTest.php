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
		$user = $this->userMocker->mockSingle();
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
		Core::getConfig()->privileges->listPosts = 'power-user';
		Access::initWithoutCache();

		$user = $this->userMocker->mockSingle();
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
		Core::getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Access::initWithoutCache();

		$user = $this->userMocker->mockSingle();
		$user->setAccessRank(new AccessRank(AccessRank::PowerUser));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'own'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts, 'all'), $user));
		$this->assert->isTrue(Access::check(new Privilege(Privilege::ListPosts), $user));
	}

	public function testSubPrivilegesSubAndGeneralNormalOrder()
	{
		Core::getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Core::getConfig()->privileges->{'listPosts'} = 'admin';
		Access::initWithoutCache();
		$this->testSubPrivilegesSubAndGeneral();
	}

	public function testSubPrivilegesSubAndGeneralReverseOrder()
	{
		Core::getConfig()->privileges->{'listPosts'} = 'admin';
		Core::getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Access::initWithoutCache();
		$this->testSubPrivilegesSubAndGeneral();
	}

	protected function testSubPrivilegesSubAndGeneral()
	{
		$user = $this->userMocker->mockSingle();
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
		Core::getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Core::getConfig()->privileges->{'listPosts.all'} = 'admin';
		Core::getConfig()->privileges->{'listPosts'} = 'nobody';
		Access::initWithoutCache();
		$this->testSubPrivilegesMultipleSubAndGeneral();
	}

	public function testSubPrivilegesMultipleSubAndGeneralReverseOrder()
	{
		Core::getConfig()->privileges->{'listPosts'} = 'nobody';
		Core::getConfig()->privileges->{'listPosts.own'} = 'power-user';
		Core::getConfig()->privileges->{'listPosts.all'} = 'admin';
		Access::initWithoutCache();
		$this->testSubPrivilegesMultipleSubAndGeneral();
	}

	protected function testSubPrivilegesMultipleSubAndGeneral()
	{
		$user = $this->userMocker->mockSingle();
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
