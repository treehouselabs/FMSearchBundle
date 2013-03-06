<?php

namespace FM\SearchBundle\Pager;

/**
 * Pagination with x pages on the left and right of the current page.
 */
class SwarmedPagination extends AbstractPagination
{
    private $swarmSize = 10;

    public function setSwarmSize($size)
    {
        $this->validateInteger($size);
        $this->swarmSize = (int) $size;

        $this->recalculate();
    }

    protected function getPageNumbers()
    {
        $this->check();

        $pages = array();

        // pages on the left
        $min = max(1, ($this->currentPage - $this->swarmSize));
        $pages = range($min, $this->currentPage);

        // pages on the right
        $max = min($this->pageCount, ($this->currentPage + $this->swarmSize));
        $pages = array_merge($pages, range($this->currentPage, $max));

        return $pages;
    }
}
