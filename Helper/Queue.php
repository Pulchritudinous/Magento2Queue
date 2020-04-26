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

use Magento\Framework\Xml\Generator;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class Queue
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManager
     */
    private $_objectManager;

    /**
     * Transaction factory
     *
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $_transactionFactory;

    /**
     * Array helper
     *
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $_arrHelper;

    /**
     * Worker config instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker\Config
     */
    protected $_workerConfig = null;

    /**
     * Db helper instance
     *
     * @var \Pulchritudinous\Queue\Helper\Db
     */
    protected $_dbHelper = null;

    /**
     * Worker config reader instance
     *
     * @var \Pulchritudinous\Queue\Config\Worker\Reader
     */
    protected $_workerConfigReader = null;

    /**
     * Initial constructor
     *
     * @param \Magento\Framework\Stdlib\ArrayManager $arrHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Pulchritudinous\Queue\Config\Worker\Reader $workerConfigReader
     * @param \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig
     * @param \Pulchritudinous\Queue\Helper\Db $dbHelper
     */
    public function __construct(
        \Magento\Framework\Stdlib\ArrayManager $arrHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Pulchritudinous\Queue\Config\Worker\Reader $workerConfigReader,
        \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig,
        \Pulchritudinous\Queue\Helper\Db $dbHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->_transactionFactory = $transactionFactory;
        $this->_arrHelper = $arrHelper;
        $this->_workerConfig = $workerConfig;
        $this->_dbHelper = $dbHelper;
        $this->_workerConfigReader = $workerConfigReader;
    }

    /**
     * Add a job to the queue that will be asynchronously handled by a worker.
     *
     * @param  string $worker
     * @param  array $payload
     * @param  array $options
     *
     * @return Labour
     *
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function add(string $worker, array $payload = [], array $options = []) :? Labour
    {
        $config = $this->_workerConfig->getWorkerConfigById($worker);

        if (null === $config) {
            throw new NoSuchEntityException("Unable to find worker with name {$worker}");
        }

        $byRecurring = (bool) $this->_arrHelper->get('by_recurring', $options, false);
        $options = $this->_arrHelper->remove('by_recurring', $options);

        $options = array_merge(
            $config,
            $options
        );

        $this->_validateOptions($options);

        $identity = $this->_arrHelper->get('identity', $options, '');

        if (!is_string($identity)) {
            throw new InputException('Identity needs to be of type string');
        }

        $delay = $this->_arrHelper->get('delay', $options, null);
        $rule = $this->_arrHelper->get('rule', $options);
        $options = $this->_arrHelper->set('execute_at', $options, $this->_getWhen($delay ?: (int) $delay));
        $options = $this->_arrHelper->remove('delay', $options);

        if (Labour::RULE_IGNORE === $rule) {
            $hasLabour = $this->_dbHelper->hasUnprocessedWorkerIdentity($worker, $identity);

            if (true === $hasLabour) {
                return null;
            }
        } elseif (Labour::RULE_REPLACE === $rule) {
            $this->_dbHelper->setStatusOnUnprocessedByWorkerIdentity('replaced', $worker, $identity);
        }

        $labour = $this->_objectManager->create('\Pulchritudinous\Queue\Model\Labour');

        $labour
            ->setWorker($worker)
            ->addData($options)
            ->setIdentity($identity)
            ->setAttempts(0)
            ->setByRecurring($byRecurring)
            ->setPayload($this->_ensureArrayData($payload))
            ->setStatus(Labour::STATUS_PENDING)
            ->save();

        return $labour;
    }

    /**
     * Receive number of queued labours.
     *
     * @param  integer $batchSize
     *
     * @return null|array
     */
    public function receive(int $batchSize = 1) :? array
    {
        $batchSize = max(1, $batchSize);
        $running = [];
        $pageNr = 0;
        $runningCollection = $this->getRunning();
        $runningWorkerCount = [];
        $labours = [];

        foreach ($runningCollection as $labour) {
            $identity = "{$labour->getWorker()}-{$labour->getIdentity()}";

            $running[$identity] = $identity;

            if (!isset($runningWorkerCount[$labour->getWorker()])) {
                $runningWorkerCount[$labour->getWorker()] = 0;
            }

            $runningWorkerCount[$labour->getWorker()]++;
        }

        $iterator = $this->_objectManager->create('\Pulchritudinous\Queue\Model\LabourIterator', [
            'resourceCollection' => $this->_getQueueCollection()
        ]);

        foreach ($iterator as $labour) {
            $config = $this->_workerConfig->getWorkerConfigById($labour->getWorker());
            $rule = $this->_arrHelper->get('rule', $config);
            $limit = $this->_arrHelper->get('limit', $config);

            if (null === $config) {
                continue;
            }

            $identity = "{$labour->getWorker()}-{$labour->getIdentity()}";
            $currentRunning = isset($runningWorkerCount[$labour->getWorker()])
                ? $runningWorkerCount[$labour->getWorker()]
                : 0;

            if ($labour::RULE_WAIT === $rule && isset($running[$identity])) {
                continue;
            }

            if ($limit && $limit <= $currentRunning) {
                continue;
            }

            $labours[] = $this->_beforeReturn($labour, $config);

            if (count($labours) >= $batchSize) {
                break;
            }
        }

        if (empty($labours)) {
            return null;
        }

        return $labours;
    }

    /**
     * Get allowed options.
     *
     * @param  array $options
     *
     * @return boolean
     */
    protected function _validateOptions(array $options) : bool
    {
        $options = $this->_arrHelper->remove('code', $options);

        array_walk_recursive($options, function (&$value) { $value = (string) $value; });

        $xml = (new Generator)->arrayToXml([
            'config' => [
                'queue' => [
                    'worker' => [
                        '_attribute' => [
                            'id' => 'validation'
                        ],
                        '_value' => $options
                    ],
                ]
            ]
        ])->getDom()->saveXML();

        $this->_workerConfigReader->validateWorderConfig($xml);

        return true;
    }

    /**
     * Parses when the labour should be executed.
     *
     * @param  null|int $delay
     *
     * @return int
     */
    protected function _getWhen(int $delay = null) : int
    {
        return $this->_objectManager->create('Pulchritudinous\Queue\Helper\Data')->getWhen($delay);
    }

    /**
     * Ensure that array data does not contain objects.
     *
     * @param  array $data
     *
     * @return array
     */
    protected function _ensureArrayData(array $data) : array
    {
        $return = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->_ensureArrayData($value);

                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Get queued labour collection.
     *
     * @return Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
     */
    protected function _getQueueCollection() : \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
    {
        $collection = $this->_objectManager->create('Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection')
            ->addFieldToFilter('status', ['eq' => Labour::STATUS_PENDING])
            ->addFieldToFilter('execute_at', ['lteq' => time()])
            ->setOrder('priority', 'ASC')
            ->setOrder('created_at', 'ASC');

        return $collection;
    }

    /**
     * Before labour is returned.
     *
     * @param  Labour $labour
     * @param  array $config
     *
     * @return Labour
     */
    protected function _beforeReturn(Labour $labour, array $config) : Labour
    {
        $rule = $this->_arrHelper->get('rule', $config);
        $transaction = $this->_transactionFactory->create();

        if ($rule === Labour::RULE_BATCH) {
            $queueCollection = $this->_getQueueCollection()
                ->addFieldToFilter('identity', ['eq' => $labour->getIdentity()])
                ->addFieldToFilter('worker', ['eq' => $labour->getWorker()]);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $labour->getId()) {
                    $bundle->addData([
                        'parent_id' => $labour->getId(),
                        'status' => Labour::STATUS_DEPLOYED,
                    ]);

                    $transaction->addObject($bundle);
                }
            }
        }

        $labour->addData([
            'status' => Labour::STATUS_DEPLOYED,
        ]);

        $transaction->addObject($labour);
        $transaction->save();

        return $labour;
    }

    /**
     * Get all running labours.
     *
     * @param  boolean $includeUnknown
     *
     * @return Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
     */
    public function getRunning(bool $includeUnknown = false) : \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
    {
        $statuses = [
            Labour::STATUS_DEPLOYED,
            Labour::STATUS_RUNNING,
        ];

        if (true === $includeUnknown) {
            $statuses[] = Labour::STATUS_UNKNOWN;
        }

        $collection = $this->_objectManager->create('Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection')
            ->addFieldToFilter('status', ['in' => $statuses]);

        return $collection;
    }

    /**
     * Mark labour as finished.
     *
     * @param  Labour
     *
     * @return Labour
     */
    public function finish(Labour $labour) : Labour
    {
        $data = [
            'status' => Labour::STATUS_FINISHED,
            'finished_at' => time(),
        ];

        $labour->addData($data)->save();

        return $labour;
    }

    /**
     * Reschedule labour to be run at a later time.
     *
     * @param  Labour $labour
     * @param  null|integer $delay
     *
     * @return boolean
     */
    public function reschedule(Labour $labour, $delay = null) : bool
    {
        $config = $this->_workerConfig->getWorkerConfigById($labour->getWorker());

        $labour
            ->setStatus(Labour::STATUS_PENDING)
            ->setAttempts((int) $labour->getAttempts() + 1)
            ->setExecuteAt($this->_getWhen($delay))
            ->save();

        return true;
    }

    /**
     * Clear missed recurring labours.
     *
     * @return Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
     */
    public function clearMissingRecurring() : \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
    {
        $collection = $this->objectManager->create('Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection')
            ->addFieldToFilter('status', ['eq' => Labour::STATUS_PENDING])
            ->addFieldToFilter('by_recurring', ['eq' => 1])
            ->addFieldToFilter('execute_at', ['lt' => time()]);

        foreach ($collection as $labour) {
            $labour->setAsSkipped();
        }

        return $collection;
    }
}

