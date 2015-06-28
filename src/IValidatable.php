<?php
namespace Szurubooru;
use Szurubooru\Validator;

interface IValidatable
{
    public function validate(Validator $validator);
}
