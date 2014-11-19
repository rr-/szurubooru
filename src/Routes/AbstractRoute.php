<?php
namespace Szurubooru\Routes;

abstract class AbstractRoute
{
	public abstract function getMethods();

	public abstract function getUrl();

	public abstract function work();
}
