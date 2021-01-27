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

namespace Pulchritudinous\Queue\Helper;

use Magento\Framework\Xml\Generator;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

use Pulchritudinous\Queue\Model\Labour;

class Queue
{
    /**
     * Transaction factory
     *
     * @var \Magento\Framework\DB\TransactionFactory
     */
    public $transactionFactory;

    /**
     * Array helper
     *
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    public $arrHelper;

    /**
     * Worker config instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker\Config
     */
    public $workerConfig;

    /**
     * Db helper instance
     *
     * @var \Pulchritudinous\Queue\Helper\Db
     */
    public $dbHelper;

    /**
     * Worker config reader instance
     *
     * @var \Pulchritudinous\Queue\Config\Worker\Reader
     */
    public $workerConfigReader;

    /**
     * Queue helper data
     *
     * @var \Pulchritudinous\Queue\Helper\Data
     */
    public $queueHelperData;

    /**
     * Labour factory
     *
     * @var \Pulchritudinous\Queue\Model\LabourFactory
     */
    public $labourFactory;

    /**
     * Initial constructor
     *
     * @param \Magento\Framework\Stdlib\ArrayManager $arrHelper
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Pulchritudinous\Queue\Helper\Db $dbHelper
     * @param \Pulchritudinous\Queue\Helper\Data $queueHelperData,
     * @param \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig,
     * @param \Pulchritudinous\Queue\Model\LabourFactory $labourFactory,
     * @param \Pulchritudinous\Queue\Config\Worker\Reader $workerConfigReader,
     * @param \Pulchritudinous\Queue\Model\ResourceModel\Labour\CollectionFactory $resourceCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\ArrayManager $arrHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Pulchritudinous\Queue\Helper\Db $dbHelper,
        \Pulchritudinous\Queue\Helper\Data $queueHelperData,
        \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig,
        \Pulchritudinous\Queue\Model\LabourFactory $labourFactory,
        \Pulchritudinous\Queue\Config\Worker\Reader $workerConfigReader,
        \Pulchritudinous\Queue\Model\ResourceModel\Labour\CollectionFactory $resourceCollectionFactory
    ) {
        $this->dbHelper = $dbHelper;
        $this->arrHelper = $arrHelper;
        $this->workerConfig = $workerConfig;
        $this->labourFactory = $labourFactory;
        $this->queueHelperData = $queueHelperData;
        $this->workerConfigReader = $workerConfigReader;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * Add a job to the queue that will be asynchronously handled by a worker.
     *
     * @param string $worker
     * @param array $payload
     * @param array $options
     *
     * @return Labour
     *
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function add(string $worker, array $payload = [], array $options = []) :? Labour
    {
        $config = $this->workerConfig->getWorkerConfigById($worker);

        if (null === $config) {
            $message = __(
                'Unable to find worker with name %worker',
                ['worker' => $worker]
            );

            throw new NoSuchEntityException($message);
        }

        $byRecurring = (bool) $this->arrHelper->get('by_recurring', $options, false);
        $options = $this->arrHelper->remove('by_recurring', $options);

        $identity = $this->arrHelper->get('identity', $options, '');
        $options = $this->arrHelper->remove('identity', $options);

        $options = array_merge(
            $config,
            $options
        );

        $options = $this->arrHelper->remove('recurring', $options);

        $this->validateOptions($options);

        if (!is_string($identity)) {
            throw new InputException(__('Identity needs to be of type string'));
        }

        $delay = $this->arrHelper->get('delay', $options, null);
        $rule = $this->arrHelper->get('rule', $options);
        $options = $this->arrHelper->set('execute_at', $options, $this->getWhen($delay ? ((int) $delay) : null));
        $options = $this->arrHelper->remove('delay', $options);

        if (Labour::RULE_IGNORE === $rule) {
            $hasLabour = $this->dbHelper->hasUnprocessedWorkerIdentity($worker, $identity);

            if (true === $hasLabour) {
                return null;
            }
        } elseif (Labour::RULE_REPLACE === $rule) {
            $this->dbHelper->setStatusOnUnprocessedByWorkerIdentity('replaced', $worker, $identity);
        }

        $labour = $this->labourFactory->create()
            ->setWorker($worker)
            ->addData($options)
            ->setIdentity($identity)
            ->setAttempts(0)
            ->setByRecurring($byRecurring)
            ->setPayload($this->ensureArrayData($payload))
            ->setStatus(Labour::STATUS_PENDING)
            ->save();

        return $labour;
    }

    /**
     * Receive number of queued labours.
     *
     * @param int $qty
     *
     * @return null|array
     */
    public function receive(int $qty = 1) :? array
    {
        $qty = max(1, $qty);
        $running = [];
        $runningCollection = $this->getRunning();

        foreach ($runningCollection as $labour) {
            $identity = "{$labour->getWorker()}-{$labour->getIdentity()}";

            $running[$identity] = $identity;
        }

        $collection = $this->getQueueCollection();
        $collection->setPageSize(50);

        $pages  = $collection->getLastPageNumber();
        $pageNr = 1;
        $bailout = false;
        $labours = [];

        do {
            $collection
                ->setCurPage($pageNr)
                ->load();

            foreach ($collection as $labour) {
                $config = $this->workerConfig->getWorkerConfigById($labour->getWorker());

                if (!$config || null === $config) {
                    continue;
                }

                $rule = $this->arrHelper->get('rule', $config);
                $identity = "{$labour->getWorker()}-{$labour->getIdentity()}";

                if (in_array($rule, [$labour::RULE_WAIT, $labour::RULE_BATCH]) && isset($running[$identity])) {
                    continue;
                }

                $running[$identity] = $identity;
                $labours[] = $this->beforeReturn($labour, $config);

                if (count($labours) >= $qty) {
                    $bailout = true;
                    break;
                }
            }

            $pageNr++;
            $collection->clear();
        } while ($pageNr <= $pages && $bailout == false);

        if (empty($labours)) {
            return null;
        }

        return $labours;
    }

    /**
     * Get allowed options.
     *
     * @param array $options
     *
     * @return boolean
     */
    public function validateOptions(array $options) : bool
    {
        $options = $this->arrHelper->remove('code', $options);

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

        $this->workerConfigReader->validateWorderConfig($xml);

        return true;
    }

    /**
     * Parses when the labour should be executed.
     *
     * @param null|int $delay
     *
     * @return int
     */
    public function getWhen(int $delay = null) : int
    {
        return $this->queueHelperData->getWhen($delay);
    }

    /**
     * Ensure that array data does not contain objects.
     *
     * @param  array $data
     *
     * @return array
     */
    public function ensureArrayData(array $data) : array
    {
        $return = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->ensureArrayData($value);

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
    public function getQueueCollection() : \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection
    {
        $collection = $this->resourceCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => Labour::STATUS_PENDING])
            ->addFieldToFilter('execute_at', ['lteq' => time()])
            ->setOrder('priority', 'ASC')
            ->setOrder('execute_at', 'ASC');

        return $collection;
    }

    /**
     * Before labour is returned.
     *
     * @param Labour $labour
     * @param array $config
     *
     * @return Labour
     */
    public function beforeReturn(Labour $labour, array $config) : Labour
    {
        $rule = $this->arrHelper->get('rule', $config);
        $transaction = $this->transactionFactory->create();

        if ($rule === Labour::RULE_BATCH) {
            $queueCollection = $this->getQueueCollection()
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
     * @param boolean $includeUnknown
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

        $collection = $this->resourceCollectionFactory->create()
            ->addFieldToFilter('status', ['in' => $statuses]);

        return $collection;
    }

    /**
     * Mark labour as finished.
     *
     * @param Labour
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
     * @param Labour $labour
     * @param null|int $delay
     *
     * @return boolean
     */
    public function reschedule(Labour $labour, int $delay = null) : bool
    {
        $config = $this->workerConfig->getWorkerConfigById($labour->getWorker());

        $labour
            ->setStatus(Labour::STATUS_PENDING)
            ->setAttempts((int) $labour->getAttempts() + 1)
            ->setExecuteAt($this->getWhen($delay))
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
        $collection = $this->resourceCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => Labour::STATUS_PENDING])
            ->addFieldToFilter('by_recurring', ['eq' => 1])
            ->addFieldToFilter('execute_at', ['lt' => time()]);

        foreach ($collection as $labour) {
            $labour->setAsSkipped();
        }

        return $collection;
    }

    /**
     * Before server start.
     *
     * @return Queue
     */
    public function beforeServerStart() : Queue
    {
        return $this;
    }
}

