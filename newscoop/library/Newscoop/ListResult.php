<?php
/**
 * @package Newscoop
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * Result for paginated listing, holds total count of items and items for given page.
 */
class ListResult implements Countable, IteratorAggregate
{
    /**
     * @var int
     */
    public $count = 0;

    /**
     * @var Iterator
     */
    public $items = array();

    /**
     * @return int
     */
    public function count()
    {
        return (int) $this->count;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
