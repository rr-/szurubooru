<?php
namespace Szurubooru\Tests;
use Szurubooru\Config;
use Szurubooru\Injector;
use Szurubooru\Privilege;
use Szurubooru\Tests\AbstractTestCase;

final class PrivilegeTest extends AbstractTestCase
{
    public function testConstNaming()
    {
        $refl = new \ReflectionClass(Privilege::class);
        foreach ($refl->getConstants() as $key => $value)
        {
            $value = strtoupper(ltrim(preg_replace('/[A-Z]/', '_\0', $value), '_'));
            $this->assertEquals($key, $value);
        }
    }

    public function testConfigSectionNaming()
    {
        $refl = new \ReflectionClass(Privilege::class);
        $constants = array_values($refl->getConstants());

        $config = Injector::get(Config::class);
        foreach ($config->security->privileges as $key => $value)
        {
            $this->assertTrue(in_array($key, $constants), "$key not in constants");
        }
    }
}
