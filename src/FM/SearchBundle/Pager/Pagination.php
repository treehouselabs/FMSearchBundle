<?php

namespace FM\SearchBundle\Pager;

interface Pagination
{
    public function __construct($totalRows, $rowsPerPage = 20);
    public function getPages(array $query = array());
    public function setCurrentPage($pageNumber);
    public function getPageCount();
    public function getRowCount();
    public function getTotalRowCount();
    public function getStart();
    public function getEnd();
}
