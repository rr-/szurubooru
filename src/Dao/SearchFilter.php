<?php
namespace Szurubooru\Dao;

class SearchFilter
{
	public $order;
	public $query;
	public $pageNumber;
	public $pageSize;

	public function __construct($pageSize, \Szurubooru\FormData\SearchFormData $searchFormData = null)
	{
		$this->pageSize = intval($pageSize);
		if ($searchFormData)
		{
			$this->query = $searchFormData->query;
			$this->order = $searchFormData->order;
			$this->pageNumber = $searchFormData->pageNumber;
		}
	}
}
