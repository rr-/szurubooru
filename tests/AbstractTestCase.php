<?php
namespace Szurubooru\Tests;
use Szurubooru\DatabaseConnection;
use Szurubooru\Injector;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Injector::init();
        date_default_timezone_set('UTC');
    }

    protected function tearDown()
    {
        TestHelper::cleanTestDirectory();
    }

    protected function mock($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

    protected function mockTransactionManager()
    {
        return new TransactionManagerMock($this->mock(DatabaseConnection::class));
    }

    protected function mockConfig($dataPath = null, $publicDataPath = null)
    {
        return TestHelper::mockConfig($dataPath, $publicDataPath);
    }

    protected function createTestDirectory()
    {
        return TestHelper::createTestDirectory();
    }

    protected function getTestFile($fileName)
    {
        return TestHelper::getTestFile($fileName);
    }

    protected function getTestFilePath($fileName)
    {
        return TestHelper::getTestFilePath($fileName);
    }

    protected function assertEntitiesEqual($expected, $actual)
    {
        if (!is_array($expected))
        {
            $expected = [$expected];
            $actual = [$actual];
        }
        $this->assertEquals(count($expected), count($actual), 'Unmatching array sizes');
        $this->assertEquals(array_keys($expected), array_keys($actual), 'Unmatching array keys');
        foreach (array_keys($expected) as $key)
        {
            if ($expected[$key] === null)
            {
                $this->assertNull($actual[$key]);
            }
            else
            {
                $this->assertNotNull($actual[$key]);
                $expectedEntity = clone($expected[$key]);
                $actualEntity = clone($actual[$key]);
                $expectedEntity->resetLazyLoaders();
                $expectedEntity->resetMeta();
                $actualEntity->resetLazyLoaders();
                $actualEntity->resetMeta();
                $this->assertEquals($expectedEntity, $actualEntity);
            }
        }
    }
}
