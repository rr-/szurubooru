<?php
namespace Szurubooru\Dao;

class SearchResult
{
	public $filter;
	public $entities;
	public $totalRecords;

	public function __construct(SearchFilter $filter, $entities, $totalRecords)
	{
		$this->filter = $filter;
		$this->entities = $entities;
		$this->totalRecords = $totalRecords;
	}
}
