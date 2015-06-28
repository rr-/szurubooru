<?php
namespace Szurubooru\Services;
use Szurubooru\Config;

class PasswordService
{
    private $config;
    private $alphabet;
    private $pattern;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->alphabet =
        [
            'c' => str_split('bcdfghjklmnpqrstvwxyz'),
            'v' => str_split('aeiou'),
            'n' => str_split('0123456789'),
        ];
        $this->pattern = str_split('cvcvnncvcv');
    }

    public function getLegacyHash($password, $salt)
    {
        //hash used by old szurubooru version
        return sha1('1A2/$_4xVa' . $salt . $password);
    }

    public function getHash($password, $salt)
    {
        return hash('sha256', $this->config->security->secret . $salt . $password);
    }

    public function isHashValid($password, $salt, $expectedPasswordHash)
    {
        $hashes =
        [
            $this->getLegacyHash($password, $salt),
            $this->getHash($password, $salt),
        ];
        return in_array($expectedPasswordHash, $hashes);
    }

    public function getRandomPassword()
    {
        $password = '';
        foreach ($this->pattern as $token)
        {
            $subAlphabet = $this->alphabet[$token];
            $character = $subAlphabet[mt_rand(0, count($subAlphabet) - 1)];
            $password .= $character;
        }
        return $password;
    }
}
