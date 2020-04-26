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

use Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection as LabourCollection;
use Pulchritudinous\Queue\Exception\RescheduleException;

abstract class WorkerAbstract
    implements WorkerInterface
{
    /**
     * Labour model.
     *
     * @var Labour
     */
    protected $_labour;

    /**
     * Config model.
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Child labour collection.
     *
     * @var null|ResourceModel\Labour\Collection
     */
    protected $_childLabour = null;

    /**
     * @param array $config
     * @param Labour $labour
     * @param LabourCollection|null $children
     */
    public function __construct(array $config, Labour $labour = null, LabourCollection $children = null)
    {
        $this->_config = $config;
        $this->_labour = $labour;
        $this->_childLabour = $children;
    }

    /**
     * Get labour object.
     *
     * @return null|Labour
     */
    protected function _getLabour() :? Labour
    {
        return $this->_labour;
    }

    /**
     * Get payload object.
     *
     * @return array
     */
    public function getPayload() : array
    {
        return !$this->_getLabour() ? [] : $this->_getLabour()->getPayload();
    }

    /**
     * Get child labour collection.
     *
     * @return LabourCollection|null
     */
    protected function _getChildLabour() : LabourCollection
    {
        return $this->_childLabour;
    }

    /**
     * Returns options to pass to $queue->add() function when scheduling
     * recurring jobs.
     *
     * Expected format:
     * [
     *      "payload" => [...],
     *      "options" => [...]
     * ]
     * @see Pulchritudinous_Queue_Model_Queue::add()
     *
     * @param  array $worderConfig
     *
     * @return array
     */
    public static function getRecurringOptions($worderConfig = []) : array
    {
        return [];
    }

    /**
     * Throw Reschedule Exception.
     *
     * @param  string $message
     *
     * @throws RescheduleException
     */
    protected function _throwRescheduleException($message)
    {
        throw new RescheduleException($message);
    }
}

