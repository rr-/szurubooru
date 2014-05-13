<?php
class ListUsersJobTest extends AbstractTest
{
	public function testPaging()
	{
		$this->grantAccess('listUsers');

		$users = $this->userMocker->mockMultiple(3);
		getConfig()->browsing->usersPerPage = 2;

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListUsersJob(), []);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(2, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListUsersJob(), [JobArgs::ARG_PAGE_NUMBER => 2]);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(2, $ret->page);
	}

	public function testOrderDate()
	{
		$this->grantAccess('listUsers');

		$users = $this->userMocker->mockMultiple(3);
		$users[0]->setJoinTime(mktime(0, 0, 0, 10, 23, 1990));
		$users[1]->setJoinTime(mktime(0, 0, 0, 10, 22, 1990));
		$users[2]->setJoinTime(mktime(0, 0, 0, 10, 21, 1990));
		UserModel::save($users);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListUsersJob, [JobArgs::ARG_QUERY => 'order:date']);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual($users[0]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($users[1]->getName(), $ret->entities[1]->getName());
		$this->assert->areEqual($users[2]->getName(), $ret->entities[2]->getName());

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListUsersJob, [JobArgs::ARG_QUERY => '-order:date']);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual($users[2]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($users[1]->getName(), $ret->entities[1]->getName());
		$this->assert->areEqual($users[0]->getName(), $ret->entities[2]->getName());
	}

	public function testOrderAlphanumeric()
	{
		$this->grantAccess('listUsers');

		$users = $this->userMocker->mockMultiple(2);
		$users[0]->setName('alice');
		$users[1]->setName('bob');
		UserModel::save($users);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListUsersJob, [JobArgs::ARG_QUERY => 'order:alpha']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($users[1]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($users[0]->getName(), $ret->entities[1]->getName());

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListUsersJob, [JobArgs::ARG_QUERY => '-order:alpha']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($users[0]->getName(), $ret->entities[0]->getName());
		$this->assert->areEqual($users[1]->getName(), $ret->entities[1]->getName());
	}
}
