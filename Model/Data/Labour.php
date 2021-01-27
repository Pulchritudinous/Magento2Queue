<?php
declare(strict_types=1);

namespace Pulchritudinous\Queue\Model\Data;

use Pulchritudinous\Queue\Api\Data\LabourInterface;

class Labour extends \Magento\Framework\Api\AbstractExtensibleObject implements LabourInterface
{

    /**
     * Get labour_id
     * @return string|null
     */
    public function getLabourId()
    {
        return $this->_get(self::LABOUR_ID);
    }

    /**
     * Set labour_id
     * @param string $labourId
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setLabourId($labourId)
    {
        return $this->setData(self::LABOUR_ID, $labourId);
    }

    /**
     * Get id
     * @return string|null
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }

    /**
     * Set id
     * @param string $id
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Pulchritudinous\Queue\Api\Data\LabourExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Pulchritudinous\Queue\Api\Data\LabourExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Pulchritudinous\Queue\Api\Data\LabourExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get parent_id
     * @return string|null
     */
    public function getParentId()
    {
        return $this->_get(self::PARENT_ID);
    }

    /**
     * Set parent_id
     * @param string $parentId
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setParentId($parentId)
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * Get worker
     * @return string|null
     */
    public function getWorker()
    {
        return $this->_get(self::WORKER);
    }

    /**
     * Set worker
     * @param string $worker
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setWorker($worker)
    {
        return $this->setData(self::WORKER, $worker);
    }

    /**
     * Get identity
     * @return string|null
     */
    public function getIdentity()
    {
        return $this->_get(self::IDENTITY);
    }

    /**
     * Set identity
     * @param string $identity
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setIdentity($identity)
    {
        return $this->setData(self::IDENTITY, $identity);
    }

    /**
     * Get priority
     * @return string|null
     */
    public function getPriority()
    {
        return $this->_get(self::PRIORITY);
    }

    /**
     * Set priority
     * @param string $priority
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setPriority($priority)
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    /**
     * Get payload
     * @return string|null
     */
    public function getPayload()
    {
        return $this->_get(self::PAYLOAD);
    }

    /**
     * Set payload
     * @param string $payload
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setPayload($payload)
    {
        return $this->setData(self::PAYLOAD, $payload);
    }

    /**
     * Get status
     * @return string|null
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * Set status
     * @param string $status
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get attempts
     * @return string|null
     */
    public function getAttempts()
    {
        return $this->_get(self::ATTEMPTS);
    }

    /**
     * Set attempts
     * @param string $attempts
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setAttempts($attempts)
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    /**
     * Get pid
     * @return string|null
     */
    public function getPid()
    {
        return $this->_get(self::PID);
    }

    /**
     * Set pid
     * @param string $pid
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setPid($pid)
    {
        return $this->setData(self::PID, $pid);
    }

    /**
     * Get by_recurring
     * @return string|null
     */
    public function getByRecurring()
    {
        return $this->_get(self::BY_RECURRING);
    }

    /**
     * Set by_recurring
     * @param string $byRecurring
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setByRecurring($byRecurring)
    {
        return $this->setData(self::BY_RECURRING, $byRecurring);
    }

    /**
     * Get execute_at
     * @return string|null
     */
    public function getExecuteAt()
    {
        return $this->_get(self::EXECUTE_AT);
    }

    /**
     * Set execute_at
     * @param string $executeAt
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setExecuteAt($executeAt)
    {
        return $this->setData(self::EXECUTE_AT, $executeAt);
    }

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get started_at
     * @return string|null
     */
    public function getStartedAt()
    {
        return $this->_get(self::STARTED_AT);
    }

    /**
     * Set started_at
     * @param string $startedAt
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setStartedAt($startedAt)
    {
        return $this->setData(self::STARTED_AT, $startedAt);
    }

    /**
     * Get finished_at
     * @return string|null
     */
    public function getFinishedAt()
    {
        return $this->_get(self::FINISHED_AT);
    }

    /**
     * Set finished_at
     * @param string $finishedAt
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function setFinishedAt($finishedAt)
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }
}

