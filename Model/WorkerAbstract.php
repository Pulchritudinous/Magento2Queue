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

use Pulchritudinous\Queue\Exception\RescheduleException;
use Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection as LabourCollection;

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
     * Initial configuration.
     *
     * @param Labour $labour
     */
    public function __construct(Labour $labour = null)
    {
        $this->_labour = $labour;
    }

    /**
     * Get labour object.
     *
     * @return null|Labour
     */
    public function getLabour() :? Labour
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
        return !$this->getLabour() ? [] : $this->getLabour()->getPayload();
    }

    /**
     * Get worker config.
     *
     * @return array
     */
    public function getWorkerConfig() : array
    {
        return !$this->getLabour() ? [] : $this->getLabour()->getWorkerConfig();
    }

    /**
     * Get child collection.
     *
     * @return LabourCollection|null
     */
    public function getBatchCollection() :? LabourCollection
    {
        return !$this->getLabour() ? null : $this->getLabour()->getBatchCollection();
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
     *
     * @param  array $workerConfig
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

