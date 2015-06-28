<?php
namespace Szurubooru\Entities;

final class GlobalParam extends Entity
{
    const KEY_FEATURED_POST_USER = 'featuredPostUser';
    const KEY_FEATURED_POST = 'featuredPost';
    const KEY_POST_SIZE = 'postSize';
    const KEY_POST_COUNT = 'postCount';

    private $key;
    private $value;

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
