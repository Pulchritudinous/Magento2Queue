<?php declare(strict_types=1);
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2021 Pulchritudinous
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

namespace Pulchritudinous\Queue\Api\Data;

interface LabourInterface
    extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const ID = 'id';
    const PARENT_ID = 'parent_id';
    const WORKER = 'worker';
    const IDENTITY = 'identity';
    const PRIORITY = 'priority';
    const PAYLOAD = 'payload';
    const STATUS = 'status';
    const PID = 'pid';
    const BY_RECURRING = 'by_recurring';
    const ATTEMPTS = 'attempts';
    const UPDATED_AT = 'updated_at';
    const STARTED_AT = 'started_at';
    const CREATED_AT = 'created_at';
    const EXECUTE_AT = 'execute_at';
    const FINISHED_AT = 'finished_at';

    /**
     * Get id
     *
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     *
     * @param string $id
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setId($id);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Pulchritudinous\Queue\Api\Data\LabourExtensionInterface $extensionAttributes
     *
     * @return $this
     */
    public function setExtensionAttributes(
        \Pulchritudinous\Queue\Api\Data\LabourExtensionInterface $extensionAttributes
    );

    /**
     * Get parent_id
     *
     * @return string|null
     */
    public function getParentId();

    /**
     * Set parent_id
     *
     * @param string $parentId
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setParentId($parentId);

    /**
     * Get worker
     * @return string|null
     */
    public function getWorker();

    /**
     * Set worker
     *
     * @param string $worker
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setWorker($worker);

    /**
     * Get identity
     *
     * @return string|null
     */
    public function getIdentity();

    /**
     * Set identity
     *
     * @param string $identity
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setIdentity($identity);

    /**
     * Get priority
     *
     * @return string|null
     */
    public function getPriority();

    /**
     * Set priority
     *
     * @param string $priority
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setPriority($priority);

    /**
     * Get payload
     *
     * @return string|null
     */
    public function getPayload();

    /**
     * Set payload
     *
     * @param string $payload
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setPayload($payload);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setStatus($status);

    /**
     * Get attempts
     *
     * @return string|null
     */
    public function getAttempts();

    /**
     * Set attempts
     *
     * @param string $attempts
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setAttempts($attempts);

    /**
     * Get pid
     *
     * @return string|null
     */
    public function getPid();

    /**
     * Set pid
     *
     * @param string $pid
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setPid($pid);

    /**
     * Get by_recurring
     *
     * @return string|null
     */
    public function getByRecurring();

    /**
     * Set by_recurring
     * @param string $byRecurring
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setByRecurring($byRecurring);

    /**
     * Get execute_at
     *
     * @return string|null
     */
    public function getExecuteAt();

    /**
     * Set execute_at
     *
     * @param string $executeAt
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setExecuteAt($executeAt);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $createdAt
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     *
     * @param string $updatedAt
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get started_at
     *
     * @return string|null
     */
    public function getStartedAt();

    /**
     * Set started_at
     *
     * @param string $startedAt
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setStartedAt($startedAt);

    /**
     * Get finished_at
     * @return string|null
     */
    public function getFinishedAt();

    /**
     * Set finished_at
     *
     * @param string $finishedAt
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setFinishedAt($finishedAt);
}

