<?php
namespace Szurubooru\Services;

class TokenService
{
	private $tokenDao;

	public function __construct(\Szurubooru\Dao\TokenDao $tokenDao)
	{
		$this->tokenDao = $tokenDao;
	}

	public function getByName($tokenName)
	{
		$token = $this->tokenDao->getByName($tokenName);
		if (!$token)
			throw new \InvalidArgumentException('Token with identifier "' . $tokenName . '" not found.');
		return $token;
	}

	public function invalidateByToken($tokenName)
	{
		return $this->tokenDao->deleteByName($tokenName);
	}

	public function invalidateByUser(\Szurubooru\Entities\User $user)
	{
		return $this->tokenDao->deleteByAdditionalData($user->id);
	}

	public function createAndSaveToken(\Szurubooru\Entities\User $user, $tokenPurpose)
	{
		$token = new \Szurubooru\Entities\Token();
		$token->name = hash('sha256', $user->name . '/' . microtime(true));
		$token->additionalData = $user->id;
		$token->purpose = $tokenPurpose;
		$this->invalidateByUser($user);
		$this->tokenDao->save($token);
		return $token;
	}
}
