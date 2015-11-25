<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\TokenDao;
use Szurubooru\Entities\Token;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class TokenDaoTest extends AbstractDatabaseTestCase
{
    public function testRetrievingByValidName()
    {
        $token = new Token();
        $token->setName('test');
        $token->setPurpose(Token::PURPOSE_LOGIN);

        $tokenDao = $this->getTokenDao();
        $tokenDao->save($token);
        $expected = $token;
        $actual = $tokenDao->findByName($token->getName());

        $this->assertEntitiesEqual($actual, $expected);
    }

    public function testRetrievingByInvalidName()
    {
        $tokenDao = $this->getTokenDao();
        $actual = $tokenDao->findByName('rubbish');

        $this->assertNull($actual);
    }

    public function testRetrievingByAdditionalDataAndPurpose()
    {
        $token = new Token();
        $token->setName('test');
        $token->setPurpose(Token::PURPOSE_LOGIN);

        $tokenDao = $this->getTokenDao();
        $tokenDao->save($token);
        $expected = $token;

        $this->assertEntitiesEqual($expected, $tokenDao->findByAdditionalDataAndPurpose(null, Token::PURPOSE_LOGIN));
        $this->assertNull($tokenDao->findByAdditionalDataAndPurpose(null, Token::PURPOSE_ACTIVATE));
    }

    private function getTokenDao()
    {
        return new TokenDao($this->databaseConnection);
    }
}
