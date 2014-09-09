<?php
namespace Szurubooru;

interface IValidatable
{
	public function validate(\Szurubooru\Validator $validator);
}
