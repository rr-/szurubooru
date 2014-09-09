<?php
namespace Szurubooru\FormData;

class SearchFormData implements \Szurubooru\IValidatable
{
	public $query;
	public $order;
	public $pageNumber;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->query = trim($inputReader->query);
			$this->order = trim($inputReader->order);
			$this->pageNumber = intval($inputReader->page);
		}
	}

	public function validate(\Szurubooru\Validator $validator = null)
	{
		$validator->validateNumber($this->pageNumber);
	}
}
