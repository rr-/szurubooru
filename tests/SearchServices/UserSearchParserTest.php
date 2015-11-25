<?php
namespace Szurubooru\Tests\SearchService;
use \Szurubooru\Helpers\InputReader;
use \Szurubooru\Search\Filters\UserFilter;
use \Szurubooru\Search\ParserConfigs\UserSearchParserConfig;
use \Szurubooru\Search\SearchParser;
use \Szurubooru\Tests\AbstractTestCase;

final class UserSearchParserTest extends AbstractTestCase
{
    private $inputReader;
    private $searchParser;

    public function setUp()
    {
        parent::setUp();
        $this->inputReader = new InputReader;
        $this->searchParser = new SearchParser(new UserSearchParserConfig);
    }

    public function testDefaultOrder()
    {
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $this->assertOrderEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_ASC], $filter->getOrder());
    }

    public function testInvalidOrder()
    {
        $this->inputReader->order = 'invalid,desc';
        $this->setExpectedException(\Exception::class);
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
    }

    public function testInvalidOrderDirection()
    {
        $this->inputReader->order = 'name,invalid';
        $this->setExpectedException(\Exception::class);
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
    }

    public function testParamOrder()
    {
        $this->inputReader->order = 'name,desc';
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $this->assertOrderEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_DESC], $filter->getOrder());
    }

    public function testTokenOverwriteDefaultOrder()
    {
        $this->inputReader->query = 'order:name,desc';
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $this->assertOrderEquals([UserFilter::ORDER_NAME => UserFilter::ORDER_DESC], $filter->getOrder());
    }

    public function testTokenOrder()
    {
        $this->inputReader->query = 'order:creation_time,desc';
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $this->assertOrderEquals([
            UserFilter::ORDER_CREATION_TIME => UserFilter::ORDER_DESC,
            UserFilter::ORDER_NAME => UserFilter::ORDER_ASC],
            $filter->getOrder());
    }

    public function testParamAndTokenOrder()
    {
        $this->inputReader->order = 'name,desc';
        $this->inputReader->query = 'order:creation_time,desc';
        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $this->assertOrderEquals([
            UserFilter::ORDER_CREATION_TIME => UserFilter::ORDER_DESC,
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
