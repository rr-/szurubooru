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
		$this->assertEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_ASC], $filter->getOrder());
	}

	public function testParamOrder()
	{
		$this->inputReader->order = 'name,desc';
		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$this->assertEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_DESC], $filter->getOrder());
	}
}
