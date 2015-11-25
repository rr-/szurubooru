<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class GlobalParamDaoTest extends AbstractDatabaseTestCase
{
    public function testSettingValues()
    {
        $expected = new GlobalParam();
        $expected->setKey('key');
        $expected->setValue('test');

        $globalParamDao = $this->getGlobalParamDao();
        $globalParamDao->save($expected);

        $actual = $globalParamDao->findByKey($expected->getKey());
        $this->assertEntitiesEqual($actual, $expected);
    }

    public function testInsertingSameKeyTwice()
    {
        $param1 = new GlobalParam();
        $param1->setKey('key');
        $param1->setValue('value1');

        $param2 = new GlobalParam();
        $param2->setKey('key');
        $param2->setValue('value2');

        $globalParamDao = $this->getGlobalParamDao();
        $globalParamDao->save($param1);
        $globalParamDao->save($param2);

        $this->assertEquals([$param2], array_values($globalParamDao->findAll()));
    }

    public function testUpdatingValues()
    {
        $expected = new GlobalParam();
        $expected->setKey('key');
        $expected->setValue('test');

        $globalParamDao = $this->getGlobalParamDao();
        $globalParamDao->save($expected);

        $expected->setKey('key2');
        $expected->setValue('test2');
        $globalParamDao->save($expected);

        $actual = $globalParamDao->findByKey($expected->getKey());
        $this->assertEntitiesEqual($actual, $expected);
    }

    public function testRetrievingUnknownKeys()
    {
        $globalParamDao = $this->getGlobalParamDao();
        $actual = $globalParamDao->findByKey('hey i dont exist');
        $this->assertNull($actual);
    }

    private function getGlobalParamDao()
    {
        return new GlobalParamDao($this->databaseConnection);
    }
}
