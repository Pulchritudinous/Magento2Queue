<?php declare(strict_types=1);
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Pulchritudinous
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Pulchritudinous\Queue\Model;

use \Pulchritudinous\Queue\Model\Labour;

class LabourIterator
    implements \Iterator
{
    /**
     * Number of rows collected each time.
     *
     * @var integer
     */
    const PAGE_SIZE = 50;

    /**
     * Entity collection.
     *
     * @var Varien_Data_Collection
     */
    protected $_collection;

    /**
     * Current item.
     *
     * @var Varien_Object|null
     */
    protected $_data;

    /**
     * Current slice.
     *
     * @var array
     */
    protected $_slice = [];

    /**
     * Is initialized.
     *
     * @var boolean
     */
    protected $_initiated = false;

    /**
     * Is valid.
     *
     * @var boolean
     */
    protected $_isValid = false;

    /**
     * Current slice index.
     *
     * @var integer
     */
    protected $_index = 0;

    /**
     * Current page.
     *
     * @var integer
     */
    protected $_page;

    /**
     * Initial page.
     *
     * @var integer
     */
    protected $_initPage;

    /**
     * Last page.
     *
     * @var integer
     */
    protected $_lastPage;

    /**
     * Labour instance
     *
     * @var \Pulchritudinous\Queue\Model\Labour
     */
    protected $labourModel;

    /**
     * Labour collection instance
     *
     * @var \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
     */
    protected $resourceCollection;

    /**
     * Initialize collection iterator.
     *
     * @param \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection $resourceCollection
     */
    public function __construct(
        \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection $resourceCollection
    ) {
        $resourceCollection->setPageSize(self::PAGE_SIZE);

        $this->_initPage = $resourceCollection->getCurPage();
        $this->_page = $resourceCollection->getCurPage();
        $this->_lastPage = $resourceCollection->getLastPageNumber();

        $this->_collection = $resourceCollection;

        $this->_loadMore();
    }

    /**
     * Load more data into slice.
     *
     * @return LabourIterator
     */
    protected function _loadMore() : LabourIterator
    {
        $this->_index = 0;
        $this->_slice = [];

        if ($this->_page > $this->_lastPage) {
            return $this;
        }

        $collection = $this->_collection
            ->setCurPage($this->_page++)
            ->load();

        foreach ($collection as $item) {
            $this->_slice[] = $item;
        }

        $this->rewind();
        $this->_isValid = count($this->_slice) > 0;

        $this->_lastPage = $this->_collection
             ->getLastPageNumber();

        $this->_collection->clear();

        return $this;
    }

    /**
     * Reset collection to initial state.
     */
    public function rewind() : void
    {
        reset($this->_slice);
    }

    /**
     * Return current item.
     *
     * @return Labour|null
     */
    public function current() :? Labour
    {
        return current($this->_slice);
    }

    /**
     * This method returns the current entity id.
     *
     * @return integer
     */
    public function key() : int
    {
        return ($this->valid()) ? $this->_data->getId() : null;
    }

    /**
     * Get next entity.
     *
     * @return Labour
     */
    public function next() :? Labour
    {
        $this->_data = next($this->_slice);

        if (false === $this->_data) {
            $this->_loadMore();
            $this->_data = next($this->_slice);
        }

        $this->_isValid = (false !== $this->_data);

        return $this->_data ? $this->_data : null;
    }

    /**
     * This method checks if the next row is a valid row.
     *
     * @return boolean
     */
    public function valid() : bool
    {
        return $this->_isValid;
    }
}

