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

namespace Pulchritudinous\Queue\Helper;

use Pulchritudinous\Queue\Model\Labour;

class Db
{
    /**
     * Resource connection instance
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * Labour model instance
     *
     * @var \Pulchritudinous\Queue\Model\Labour
     */
    protected $_labourModel = null;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Pulchritudinous\Queue\Model\Labour $labourModel
    ) {
        $this->_resourceConnection = $resourceConnection;
        $this->_labourModel = $labourModel;
    }

    /**
     * Set status to a unprocessed labour by worker and identity.
     *
     * @param  string $status
     * @param  string $worker
     * @param  string $identity
     *
     * @return boolean
     */
    public function setStatusOnUnprocessedByWorkerIdentity(string $status, string $worker, string $identity) : bool
    {
        $adapter = $this->_resourceConnection->getConnection();

        $data = [
            'status' => (string) $status,
        ];

        $result = $adapter->update(
            $this->getTablename('pulchritudinous_queue_labour'),
            $data,
            [
                'status = ?'    => $this->_labourModel::STATUS_PENDING,
                'worker = ?'    => (string) $worker,
                'identity = ?'  => (string) $identity,
            ]
        );

        return (bool) $result;
    }

    /**
     * Checks if labour exists in queue by worker code and identity.
     *
     * @param  string $worker
     * @param  string $identity
     *
     * @return boolean
     */
    public function hasUnprocessedWorkerIdentity(string $worker, string $identity) : bool
    {
        $adapter = $this->_resourceConnection->getConnection();

        $select = $adapter->select()
            ->from($this->getTablename('pulchritudinous_queue_labour'), 'id')
            ->where('worker = :worker')
            ->where('identity = :identity')
            ->where('status = :status');

        $bind = [
            ':worker'   => (string) $worker,
            ':identity' => (string) $identity,
            ':status'   => $this->_labourModel::STATUS_PENDING,
        ];

        return !empty($adapter->fetchOne($select, $bind));
    }

    /**
     * Update single field to labour.
     *
     * @param  Labour $labour
     * @param  string $field
     * @param  null|string $value
     *
     * @return boolean
     */
    public function updateLabourField(Labour $labour, string $field, string $value = null) : bool
    {
        $adapter = $this->_resourceConnection->getConnection();
        $data = [
            $field => $value,
        ];

        $result = $adapter->update(
            $labour->getResource()->getMainTable(),
            $data,
            implode(' OR ', [
                $adapter->quoteInto('id = ?', (int) $labour->getId()),
                $adapter->quoteInto('parent_id = ?', (int) $labour->getId())
            ])
        );

        if ($result) {
            $labour->setData($field, $value);
        }

        return (bool) $result;
    }

    /**
     * Get Table name using direct query
     *
     * @param  string $tableName
     *
     * @return stirng
     */
    public function getTablename(string $tableName) : string
    {
        /* Create Connection */
        $connection  = $this->_resourceConnection->getConnection();
        $tableName   = $connection->getTableName($tableName);

        return $tableName;
    }
}

