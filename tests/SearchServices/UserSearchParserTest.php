<?php
namespace Szurubooru\Tests\SearchService;

use \Szurubooru\Tests\AbstractTestCase;
use \Szurubooru\Helpers\InputReader;
use \Szurubooru\SearchServices\Filters\UserFilter;
use \Szurubooru\SearchServices\Parsers\UserSearchParser;

class UserSearchParserTest extends AbstractTestCase
{
	private $inputReader;
	private $userSearchParser;

	public function setUp()
	{
		parent::setUp();
		$this->inputReader = new InputReader;
		$this->userSearchParser = new UserSearchParser();
	}

	public function testDefaultOrder()
	{
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$this->assertOrderEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_ASC], $filter->getOrder());
	}

	public function testInvalidOrder()
	{
		$this->inputReader->order = 'invalid,desc';
		$this->setExpectedException(\Exception::class);
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
	}

	public function testInvalidOrderDirection()
	{
		$this->inputReader->order = 'name,invalid';
		$this->setExpectedException(\Exception::class);
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
	}

	public function testParamOrder()
	{
		$this->inputReader->order = 'name,desc';
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$this->assertOrderEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_DESC], $filter->getOrder());
	}

	public function testTokenOverwriteDefaultOrder()
	{
		$this->inputReader->query = 'order:name,desc';
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$this->assertOrderEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_DESC], $filter->getOrder());
	}

	public function testTokenOrder()
	{
		$this->inputReader->query = 'order:registration_time,desc';
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$this->assertOrderEquals([
			UserFilter::ORDER_REGISTRATION_TIME => UserFilter::ORDER_DESC,
			UserFilter::ORDER_NAME => UserFilter::ORDER_ASC],
			$filter->getOrder());
	}

	public function testParamAndTokenOrder()
	{
		$this->inputReader->order = 'name,desc';
		$this->inputReader->query = 'order:registration_time,desc';
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$this->assertOrderEquals([
			UserFilter::ORDER_REGISTRATION_TIME => UserFilter::ORDER_DESC,
			UserFilter::ORDER_NAME => UserFilter::ORDER_DESC],
			$filter->getOrder());
	}

	private function assertOrderEquals($expected, $actual)
	{
		$this->assertEquals($expected, $actual);
		//also test associative array's key order - something that PHPUnit doesn't seem to do
		$this->assertEquals(array_values($expected), array_values($actual));
		$this->assertEquals(array_keys($expected), array_keys($actual));
	}
}
