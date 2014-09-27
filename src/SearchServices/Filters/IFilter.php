<?php
namespace Szurubooru\SearchServices\Filters;

interface IFilter
{
	const ORDER_ASC = 1;
	const ORDER_DESC = -1;

	public function getOrder();

	public function setOrder($order);

	public function getRequirements();

	public function addRequirement(\Szurubooru\SearchServices\Requirements\Requirement $requirement);

	public function getPageSize();

	public function getPageNumber();

	public function setPageSize($pageSize);

	public function setPageNumber($pageNumber);
}
