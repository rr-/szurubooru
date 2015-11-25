<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Entities\User;
use Szurubooru\Privilege;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Tests\AbstractTestCase;

final class PrivilegeServiceTest extends AbstractTestCase
{
    private $configMock;
    private $authServiceMock;

    public function setUp()
    {
        parent::setUp();
        $this->configMock = $this->mockConfig();
        $this->authServiceMock = $this->mock(AuthService::class);
    }

    public function testReadingConfig()
    {
        $testUser = new User();
        $testUser->setName('dummy');
        $testUser->setAccessRank(User::ACCESS_RANK_POWER_USER);
        $this->authServiceMock->expects($this->atLeastOnce())->method('getLoggedInUser')->willReturn($testUser);

        $privilege = Privilege::LIST_USERS;
        $this->configMock->set('security/privileges/' . $privilege, 'powerUser');

        $privilegeService = $this->getPrivilegeService();
        $this->assertEquals([$privilege], $privilegeService->getCurrentPrivileges());
        $this->assertTrue($privilegeService->hasPrivilege($privilege));
    }

    public function testIsLoggedInByNameString()
    {
        $testUser1 = new User();
        $testUser1->setName('dummy');
        $testUser2 = new User();
        $testUser2->setName('godzilla');
        $this->authServiceMock->expects($this->atLeastOnce())->method('getLoggedInUser')->willReturn($testUser1);

        $privilegeService = $this->getPrivilegeService();
        $this->assertTrue($privilegeService->isLoggedIn($testUser1->getName()));
        $this->assertFalse($privilegeService->isLoggedIn($testUser2->getName()));
    }

    public function testIsLoggedInByEmailString()
    {
        $testUser1 = new User();
        $testUser1->setName('user1');
        $testUser1->setEmail('dummy');
        $testUser2 = new User();
        $testUser2->setName('user2');
        $testUser2->setEmail('godzilla');
        $this->authServiceMock->expects($this->atLeastOnce())->method('getLoggedInUser')->willReturn($testUser1);

        $privilegeService = $this->getPrivilegeService();
        $this->assertTrue($privilegeService->isLoggedIn($testUser1->getEmail()));
        $this->assertFalse($privilegeService->isLoggedIn($testUser2->getEmail()));
    }

    public function testIsLoggedInByUserId()
    {
        $testUser1 = new User(5);
        $testUser2 = new User(13);
        $this->authServiceMock->expects($this->atLeastOnce())->method('getLoggedInUser')->willReturn($testUser1);

        $privilegeService = $this->getPrivilegeService();
        $this->assertTrue($privilegeService->isLoggedIn($testUser1));
        $this->assertFalse($privilegeService->isLoggedIn($testUser2));
    }

    public function testIsLoggedInByUserName()
    {
        $testUser1 = new User();
        $testUser1->setName('dummy');
        $testUser2 = new User();
        $testUser2->setName('godzilla');
        $this->authServiceMock->expects($this->atLeastOnce())->method('getLoggedInUser')->willReturn($testUser1);

        $privilegeService = $this->getPrivilegeService();
        $this->assertFalse($privilegeService->isLoggedIn($testUser1));
        $this->assertFalse($privilegeService->isLoggedIn($testUser2));
    }

    public function testIsLoggedInByInvalidObject()
    {
        $testUser = new User();
        $testUser->setName('dummy');
        $this->authServiceMock->expects($this->atLeastOnce())->method('getLoggedInUser')->willReturn($testUser);

        $rubbish = new \StdClass;
        $privilegeService = $this->getPrivilegeService();
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->assertTrue($privilegeService->isLoggedIn($rubbish));
    }

    private function getPrivilegeService()
    {
        return new PrivilegeService(
            $this->configMock,
            $this->authServiceMock);
    }
}
