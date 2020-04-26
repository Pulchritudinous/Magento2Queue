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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\NotFoundException;

use Psr\Log\LoggerInterface;

use Pulchritudinous\Queue\Api\Data\LabourInterface;
use Pulchritudinous\Queue\Exception\RescheduleException;
use Pulchritudinous\Queue\Helper\Worker\Config as WorkerConfig;
use Pulchritudinous\Queue\Helper\Worker\Factory As WorkerFactory;
use Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection as LabourCollection;

class Labour
    extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_PENDING = 'pending';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_RUNNING = 'running';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_DEPLOYED = 'deployed';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_FAILED = 'failed';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_UNKNOWN = 'unknown';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_FINISHED = 'finished';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_SKIPPED = 'skipped';

    /**
     * Labour rule.
     *
     * @var string
     */
    const RULE_WAIT = 'wait';

    /**
     * Labour rule.
     *
     * @var string
     */
    const RULE_BATCH = 'batch';

    /**
     * Labour rule.
     *
     * @var string
     */
    const RULE_IGNORE = 'ignore';

    /**
     * Labour rule.
     *
     * @var string
     */
    const RULE_REPLACE = 'replace';

    /**
     * Cache event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'pulchritudinous_queue_labour';

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Data Object Helper instance
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Worker config instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker\Config
     */
    protected $workerConfig;

    /**
     * Worker factory instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker\Factory
     */
    protected $workerFactory;

    /**
     * Transaction factory
     *
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $transactionFactory;

    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManager
     */
    private $objectManager;

    /**
     * Labour constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DataObjectHelper $dataObjectHelper
     * @param \Pulchritudinous\Queue\Model\ResourceModel\Labour $resource
     * @param \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection $resourceCollection
     * @param array $data
     * @param ArrayManager $arrHelper
     * @param TransactionFactory $transactionFactory
     * @param LoggerInterface $logger
     * @param WorkerConfig $workerConfig
     * @param WorkerFactory $workerFactory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        DataObjectHelper $dataObjectHelper,
        \Pulchritudinous\Queue\Model\ResourceModel\Labour $resource,
        \Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection $resourceCollection,
        array $data = [],
        ArrayManager $arrHelper = null,
        TransactionFactory $transactionFactory = null,
        LoggerInterface $logger = null,
        WorkerConfig $workerConfig = null,
        WorkerFactory $workerFactory = null
    ) {
        $objectManager = ObjectManager::getInstance();

        $this->dataObjectHelper = $dataObjectHelper;
        $this->arrHelper = $arrHelper ?: $objectManager->get(ArrayManager::class);
        $this->transactionFactory = $transactionFactory ?: $objectManager->get(TransactionFactory::class);
        $this->logger = $logger ?: $objectManager->get(LoggerInterface::class);
        $this->workerConfig = $workerConfig ?: $objectManager->get(WorkerConfig::class);
        $this->workerFactory = $workerFactory ?: $objectManager->get(WorkerFactory::class);
        $this->objectManager = $objectManager;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Execute labour.
     */
    public function execute() : void
    {
        try {
            if (!$this->getId()) {
                throw new NotFoundException('Unable to execute labour');
            }

            if (null === $this->getWorkerConfig()) {
                throw new StateException(
                    "Unable to execute labour with ID #{$this->getId()} and worker code '{$this->getWorker()}'"
                );
            }

            $this->_beforeExecute();
            $this->_execute();
            $this->_afterExecute();
        } catch (RescheduleException $e) {
            $this->reschedule();

            $this->logger->critical($e);
        } catch (\Exception $e) {
            $this->setAsFailed();

            $this->logger->critical($e);
        }
    }

    /**
     * Get worker configuration.
     *
     * @return null|array
     */
    public function getWorkerConfig() :? array
    {
        return $this->workerConfig->getWorkerConfigById($this->getWorker());
    }

    /**
     * Mark labour as started.
     *
     * @return Labour
     */
    protected function _beforeExecute() : Labour
    {
        $config = $this->getWorkerConfig();
        $currentAttempts = $this->getAttempts() ?: 0;

        $data = [
            'status' => self::STATUS_RUNNING,
            'started_at' => time(),
            'pid' => $this->getPid(),
            'attempts' => $currentAttempts + 1,
        ];

        $transaction = $this->transactionFactory->create();

        if (self::RULE_BATCH === $this->arrHelper->get('rule', $config)) {
            $queueCollection = $this->getBatchCollection();

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * After a successful execution.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _afterExecute()
    {
        $this->setAsFinished();

        return $this;
    }

    /**
     * Execute labour.
     *
     * @return Labour
     */
    protected function _execute() : Labour
    {
        $config = $this->getWorkerConfig();
        $forge = $this->workerFactory->create($this);

        if (null === $forge) {
            $this->setAsFailed();
        } else {
            $forge->execute();
        }

        return $this;
    }

    /**
     * Reschedule labour.
     *
     * @param  boolean $detatch
     *
     * @return Labour
     */
    public function reschedule(bool $detatch = false) : Labour
    {
        $config = $this->getWorkerConfig();
        $currentAttempts = $this->getAttempts() ?: 0;

        $attempts = (int) $this->arrHelper->get('attempts', $config);

        if ($attempts < $currentAttempts) {
            return $this->setAsFailed();
        }

        $when = $this->_getWhen((int) $this->arrHelper->get('reschedule', $config));

        $data = [
            'status' => self::STATUS_PENDING,
            'execute_at' => $when,
            'parent_id' => (true === $detatch) ? null : $this->getId(),
        ];

        $transaction = $this->transactionFactory->create();

        if (self::RULE_BATCH === $config->getRule()) {
            $queueCollection = $this->getBatchCollection();

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as finished.
     *
     * @return Labour
     */
    public function setAsFinished() : Labour
    {
        $transaction = $this->transactionFactory->create();
        $data = [
            'status' => self::STATUS_FINISHED,
            'finished_at' => time(),
        ];

        foreach ($this->getBatchCollection() as $bundle) {
            $bundle->addData($data);
            $transaction->addObject($bundle);
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as failed.
     *
     * @return Labour
     */
    public function setAsFailed() : Labour
    {
        $transaction = $this->transactionFactory->create();
        $data = [
            'status' => self::STATUS_FAILED,
            'started_at' => time(),
            'finished_at' => time(),
        ];

        $queueCollection = $this->getBatchCollection();

        foreach ($queueCollection as $bundle) {
            if ($bundle->getId() != $this->getId()) {
                $bundle->addData($data);

                $transaction->addObject($bundle);
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
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
        return $this->objectManager->create('Pulchritudinous\Queue\Helper\Data')->getWhen($delay);
    }

    /**
     * Get child collection.
     *
     * @return LabourCollection
     */
    public function getBatchCollection() : LabourCollection
    {
        $collection = $this->objectManager->create('Pulchritudinous\Queue\Model\ResourceModel\Labour\Collection')
            ->addFieldToFilter('parent_id', ['eq' => $this->getId()]);

        return $collection;
    }

    /**
     * Processing object before save data
     *
     * @return Labour
     */
    public function beforeSave() : Labour
    {
        if (is_array($this->getData('payload'))) {
            $this->setPayload(json_encode($this->getData('payload')));
        }

        return parent::beforeSave();
    }

    /**
     * Object after load processing. Implemented as public interface for supporting
     * objects after load in collections
     *
     * @return Labour
     */
    public function afterLoad() : Labour
    {
        $this->setPayload(json_decode((string) $this->getPayload(), true));
        return parent::_afterLoad();
    }
}

