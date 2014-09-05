<?php
namespace Szurubooru\FormData;

class SearchFormData
{
	public $query;
	public $order;
	public $pageNumber;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->query = $inputReader->query;
			$this->order = $inputReader->order;
			$this->pageNumber = $inputReader->page;
		}
	}
}
