<?php
namespace Szurubooru\Search;
use Szurubooru\Search\Filters\IFilter;

class Result
{
    public $pageNumber;
    public $pageSize;
    public $searchFilter;
    public $entities;
    public $totalRecords;

    public function setSearchFilter(IFilter $searchFilter = null)
    {
        $this->searchFilter = $searchFilter;
    }

    public function getSearchFilter()
    {
        return $this->searchFilter;
    }

    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }

    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    public function getPageSize()
    {
        return $this->pageSize;
    }

    public function setEntities(array $entities)
    {
        $this->entities = $entities;
    }

    public function getEntities()
    {
        return $this->entities;
    }

    public function setTotalRecords($totalRecords)
    {
        $this->totalRecords = $totalRecords;
    }

    public function getTotalRecords()
    {
        return $this->totalRecords;
    }
}
