<?php

namespace FM\SearchBundle\Pager;

abstract class AbstractPagination implements Pagination
{
    const COUNT_ROW  = 'row';
    const COUNT_PAGE = 'page';

    protected $totalRows;
    protected $rowsPerPage;
    protected $pageCount;
    protected $currentPage;
    protected $rowCount;
    protected $start;
    protected $end;
    protected $pages;

    protected $calculated = false;
    protected $countingStrategy = self::COUNT_ROW;
    protected $countingStrategies = array(
        self::COUNT_ROW  => 's',
        self::COUNT_PAGE => 'p'
    );

    public function __construct($totalRows, $rowsPerPage = 20)
    {
        $this->validateInteger($totalRows);
        $this->validateInteger($rowsPerPage);

        $this->totalRows = (int) $totalRows;
        $this->rowsPerPage = (int) $rowsPerPage;
    }

    protected function validateInteger($int)
    {
        if (!is_numeric($int) || ($int < 0)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected a positive number, got %s instead',
                var_export($int, true)
            ));
        }
    }

    protected function check()
    {
        if (!$this->calculated) {
            if (!$this->currentPage) {
                throw new \RuntimeException(
                    'Please set a current page number before calculating pages'
                );
            }

            $this->recalculate();
            $this->calculated = true;
        }
    }

    public function setCurrentPage($pageNumber)
    {
        $this->validateInteger($pageNumber);
        $this->currentPage = (int) $pageNumber;

        $this->calculated = false;
    }

    public function setCountStrategy($strategy)
    {
        if (!array_key_exists($strategy, $this->countingStrategies)) {
            throw new \OutOfBoundsException(sprintf(
                'Counting strategy "%s" does not exist',
                $strategy
            ));
        }

        $this->countingStrategy = $strategy;
    }

    public function setCountStrategyParameterName($strategy, $name)
    {
        if (!array_key_exists($strategy, $this->countingStrategies)) {
            throw new \OutOfBoundsException(sprintf(
                'Counting strategy "%s" does not exist',
                $strategy
            ));
        }

        $this->countingStrategies[$strategy] = $name;

        $this->calculated = false;
    }

    public function getPageCount()
    {
        $this->check();

        return $this->pageCount;
    }

    public function getRowCount()
    {
        $this->check();

        return $this->rowCount;
    }

    public function getTotalRowCount()
    {
        $this->check();

        return $this->totalRows;
    }

    public function getStart()
    {
        $this->check();

        return $this->start;
    }

    public function getEnd()
    {
        $this->check();

        return $this->end;
    }

    protected function recalculate()
    {
        $this->pageCount = (int) ceil($this->totalRows / $this->rowsPerPage);
        $this->start = $this->currentPage * $this->rowsPerPage - $this->rowsPerPage;
        $this->rowCount = ($this->currentPage === $this->pageCount || $this->pageCount === 0) ? ($this->totalRows - $this->start) : $this->rowsPerPage;
        $this->end = $this->start + $this->rowCount;
    }

    public function getPages(array $query = array())
    {
        $this->check();

        // remove reserved keys from query if it exists
        $countParam = $this->countingStrategies[$this->countingStrategy];
        unset($query[$countParam]);

        $pages = array();

        foreach ($this->getPageNumbers() as $number) {
            $pageQuery = $query;

            // we don't need the start parameter on the first page
            if ($number > 1) {
                $pageQuery[$countParam] = ($number * $this->rowsPerPage) - $this->rowsPerPage;
            }

            $pages[$number] = $pageQuery;
        }

        ksort($pages);

        return $pages;
    }

    abstract protected function getPageNumbers();
}
