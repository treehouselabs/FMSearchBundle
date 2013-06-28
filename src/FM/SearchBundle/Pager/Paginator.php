<?php

namespace FM\SearchBundle\Pager;

class Paginator
{
    const SWARM = 'swarm';

    public static $types = array(
        self::SWARM => 'FM\SearchBundle\Pager\SwarmedPagination'
    );

    final public function paginate(Pagination $pagination, $pageNumber = 1, $query = array())
    {
        $pagination->setCurrentPage($pageNumber);

        return array(
            'page_number' => $pagination->getCurrentPage(),
            'page_count'  => $pagination->getPageCount(),
            'row_count'   => $pagination->getRowCount(),
            'total'       => $pagination->getTotalRowCount(),
            'start'       => $pagination->getStart(),
            'end'         => $pagination->getEnd(),
            'pages'       => $pagination->getPages($query),
            'previous'    => $pagination->getPreviousPage(),
            'next'        => $pagination->getNextPage()
        );
    }
}
