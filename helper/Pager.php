<?php

namespace Access2Me\Helper;


class Pager
{
    /**
     * Total count of items
     * @var int
     */
    public $itemsCount;

    /**
     * number of items per page
     * @var int
     */
    public $perPage;

    /**
     * Total pages count
     * @var int
     */
    public $count;

    /**
     * current page number
     * @var
     */
    public $page;

    /**
     * visible pages indices
     * @var array
     */
    public $pages;

    /**
     * has previous page
     * @var bool
     */
    public $hasPrev;

    /**
     * has next page
     * @var bool
     */
    public $hasNext;

    /**
     * first page is visible
     * @var bool
     */
    public $isAtStart;

    /**
     * last page is visible
     * @var bool
     */
    public $isAtEnd;

    /**
     * limit to request from storage
     * @var int
     */
    public $limit;

    /**
     * offset to request from storage
     * @var int
     */
    public $offset;

    public function __construct($itemsCount, $perPage, $currentPage, $neighbours=3)
    {
        $this->itemsCount = $itemsCount;
        $this->perPage = $perPage;
        $this->count = (int)ceil($itemsCount/$perPage);

        if ($this->count == 0) {
            $this->count = 1;
        }

        // invalid page requested
        if ($currentPage < 1 || $currentPage > $this->count) {
            $currentPage = 1;
        }

        $this->page = $currentPage;

        $this->isFirst = $this->page == 1;
        $this->isLast = $this->page == $this->count;

        // build neighbour pages
        $start = max(1, $this->page - $neighbours);
        $end = min($this->count, $this->page+$neighbours);
        $this->pages = range($start, $end);
        $this->isAtStart = $start == 1;
        $this->isAtEnd = $end == $this->count;
        $this->hasPrev = $this->page > 1;
        $this->hasNext = $this->page < $this->count;

        $this->limit = $this->perPage;
        $this->offset = ($this->page - 1)* ($this->perPage);
    }
}
