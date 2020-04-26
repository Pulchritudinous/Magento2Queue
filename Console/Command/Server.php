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

namespace Pulchritudinous\Queue\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process as SymfonyProcess;

use Psr\Log\LoggerInterface;

use Magento\Framework\FlagManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Lock\LockManagerInterface;

use Pulchritudinous\Queue\Helper\Queue;
use Pulchritudinous\Queue\Model\Server\Process;
use Pulchritudinous\Queue\Helper\Db as DbHelper;
use Pulchritudinous\Queue\Model\Labour as LabourModel;
use Pulchritudinous\Queue\Helper\Worker\Config as WorkerConfig;
use Pulchritudinous\Queue\Helper\Server\Config as ServerConfig;
use Pulchritudinous\Queue\Helper\Worker\Factory as WorkerFactory;

class Server extends Command
{
    const FLAG_CODE = 'pulchqueue';
    const LOCK_NAME = 'pulchqueue';
    const ARGUMENT_THREADS = 'threads';
    const ARGUMENT_POLL = 'poll';

    /**
     * @var LockManagerInterface
     */
    private $lockManager = null;

    /**
     * @var string
     */
    private $rootPath = null;

    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Server config instance
     *
     * @var \Pulchritudinous\Queue\Helper\Server\Config
     */
    protected $serverConfig = null;

    /**
     * Worker config instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker\Config
     */
    protected $workerConfig = null;

    /**
     * Worker factory instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker\Factory
     */
    protected $workerFactory = null;

    /**
     * Array helper
     *
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrHelper = null;

    /**
     * Flag manager instance
     *
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager = null;

    /**
     * Queue instance
     *
     * @var \Pulchritudinous\Queue\Helper\Queue
     */
    protected $queue = null;

    /**
     * Db helper instance
     *
     * @var \Pulchritudinous\Queue\Helper\Db
     */
    protected $dbHelper = null;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Should be running
     *
     * @var boolean
     */
    protected $run = true;

    /**
     * Server constructor.
     *
     * @param string $name
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        $name = null,
        LockManagerInterface $lockManager = null,
        DirectoryList $directory = null,
        WorkerConfig $serverConfig = null,
        ServerConfig $workerConfig = null,
        WorkerFactory $workerFactory = null,
        Queue $queue = null,
        DbHelper $dbHelper = null,
        ArrayManager $arrHelper = null,
        FlagManager $flagManager = null,
        LoggerInterface $logger = null
    ) {
        $objectManager = ObjectManager::getInstance();
        $directory = $directory ?: $objectManager->get(DirectoryList::class);

        $this->lockManager = $lockManager ?: $objectManager->get(LockManagerInterface::class);
        $this->workerConfig = $workerConfig ?: $objectManager->get(WorkerConfig::class);
        $this->serverConfig = $serverConfig ?: $objectManager->get(ServerConfig::class);
        $this->workerFactory = $workerFactory ?: $objectManager->get(WorkerFactory::class);
        $this->queue = $queue ?: $objectManager->get(Queue::class);
        $this->dbHelper = $dbHelper ?: $objectManager->get(DbHelper::class);
        $this->arrHelper = $arrHelper ?: $objectManager->get(ArrayManager::class);
        $this->flagManager = $flagManager ?: $objectManager->get(FlagManager::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);

        $this->objectManager = $objectManager;
        $this->rootPath = $directory->getRoot();

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pulchqueue:server');
        $this->setDescription('Start queue server');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_updateLastSchedule();

        if ($this->lockManager->isLocked(md5(self::LOCK_NAME))) {
            $output->writeln('<error>Queue is already running</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln('Server started');

        $this->lockManager->lock(self::LOCK_NAME);

        $config = $this->_getConfig();
        $queue = $this->objectManager->create('\Pulchritudinous\Queue\Helper\Queue');
        $processes = [];

        $poll = (int) $this->arrHelper->get('poll', $config);

        try {
            while ($this->run) {
                $processes = $this->_validateProcesses($processes);

                $this->addRecurringLabours($output);

                if (!$this->_canStartNext(count($processes))) {
                    sleep($poll);
                    continue;
                }

                $labours = $queue->receive($this->_canReceiveCount(count($processes)));

                if (null === $labours) {
                    sleep($poll);
                    continue;
                }

                foreach ($labours as $labour) {
                    $config = $this->workerConfig->getWorkerConfigById($labour->getWorker());

                    if (null === $config) {
                        $labour->setAsFailed();
                        continue;
                    }

                    $process = $this->_getProcess($labour);

                    $process->start();

                    $this->dbHelper->updateLabourField($labour, 'pid', (string) $process->getPid());

                    $processes[] = $process;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $msg = $e->getMessage();
            $output->writeln("<error>$msg</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * Validate all processes.
     *
     * @param  array $processes
     *
     * @return array
     */
    protected function _validateProcesses(array $processes) : array
    {
        foreach ($processes as $i => $process) {
            if (!$this->_isProcessRunning($process)) {
                unset($processes[$i]);
            }
        }

        return $processes;
    }

    /**
     * Validate single process.
     *
     * @param  Process $process
     *
     * @return boolean
     */
    protected function _isProcessRunning(Process $process) : bool
    {
        if (false === $process->isRunning()) {
            return false;
        }

        return true;
    }

    /**
     * Get process instance.
     *
     * @param  LabourModel $labour
     *
     * @return Process
     */
    protected function _getProcess(LabourModel $labour) : Process
    {
        $config = $this->workerConfig->getWorkerConfigById($labour->getWorker());
        $labourCmd = $this->objectManager->create(Labour::class);

        $labourCmd->configure();

        $php = PHP_BINDIR ? PHP_BINDIR . '/php' : 'php';
        $magentoBinary = $php . " -f {$this->rootPath}/bin/magento";
        $labourCmdName = $labourCmd->getName();
        $arguments = $labour->getId();

        $process = (new Process([
                $magentoBinary,
                $labourCmdName,
                $arguments,
            ]))
            ->setTimeout($this->arrHelper->get('timeout', $config))
            ->setLabour($labour)
            ->disableOutput();

        return $process;
    }

    /**
     * Get server configure.
     *
     * @return array
     */
    protected function _getConfig() : array
    {
        return $this->serverConfig->getServerDefaultConfig();
    }

    /**
     * Check if another process is allowed to start.
     *
     * @param integer $processCount
     *
     * @return boolean
     */
    protected function _canStartNext(int $processCount) : bool
    {
        if (0 !== $this->_canReceiveCount($processCount)) {
            return true;
        }
        return false;
    }

    /**
     * Can receive number of labours.
     *
     * @param integer $processCount
     *
     * @return integer
     */
    protected function _canReceiveCount(int $processCount) : int
    {
        $config = $this->_getConfig();

        return max(0, $this->arrHelper->get('threads', $config) - $processCount);
    }

    /**
     * Add recurring labours to queue.
     *
     * @param  OutputInterface $output
     *
     * @return Server
     */
    public function addRecurringLabours(OutputInterface $output) : Server
    {
        $workers = $this->workerConfig->getRecurringWorkers();
        $config = $this->_getConfig();
        $planAhead = $this->arrHelper->get('recurring/plan_ahead_min', $config);
        $last = (int) $this->_lastSchedule;
        $itsTime = ($last + $planAhead * 60) <= time();

        if (!$itsTime) {
            return $this;
        }

        $output->writeln('Scheduling recurring labors');

        $count = 0;

        $this->_updateLastSchedule(time());

        foreach ($workers as $worker) {
            $pattern = $this->arrHelper->get('recurring/schedule', $worker);
            $runTimes = $this->generateRunDates($pattern);

            if (empty($runTimes)) {
                continue;
            }

            $forge = $this->workerFactory->createById($worker['code']);

            if (null === $forge) {
                continue;
            }

            foreach ($runTimes as $date) {
                $opt = $forge::getRecurringOptions($worker);
                $options = (array) $this->arrHelper->get('options', $opt, []);
                $payload = (array) $this->arrHelper->get('payload', $opt, []);

                $options['by_recurring'] = true;
                $options['delay'] = $date - time();

                try {
                    $this->queue->add(
                        $worker['code'],
                        $payload,
                        $options
                    );

                    $count++;
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }

        $output->writeln("Scheduled $count recurring labors");

        return $this;
    }

    /**
     * Update date for last reschedule.
     *
     * @param  null|integer $time
     *
     * @return Server
     */
    protected function _updateLastSchedule(int $time = null)
    {
        if (is_int($time)) {
            $this->_lastSchedule = $time;
            $this->flagManager->saveFlag(self::FLAG_CODE, $time);
        } else {
            $this->_lastSchedule = $this->flagManager->getFlagData(self::FLAG_CODE);
        }

        return $this;
    }

    /**
     * Generate date times to execute labour at.
     *
     * @param  string $pattern
     *
     * @return array
     */
    public function generateRunDates(string $pattern) : array
    {
        $config = $this->_getConfig();
        $planAhead = (int) $this->arrHelper->get('recurring/plan_ahead_min', $config);
        $resolution = (int) $this->arrHelper->get('recurring/resolution', $config);

        $scheduler = $this->objectManager->get('\Magento\Cron\Model\Schedule');
        $time = time();
        $timeAhead = $time + ($planAhead * 60);
        $interval = $resolution * 60;

        $scheduler->setCronExpr($pattern);

        $runTimes = [];

        for ($time; $time < $timeAhead; $time += $interval) {
            $scheduler->setScheduledAt($time);

            $shouldAdd = $scheduler->trySchedule();

            if ($shouldAdd) {
                $runTimes[] = $time;
            }
        }

        return $runTimes;
    }

    /**
     * Make sure all labours is finished before closing the server.
     */
    public function exitStrategy()
    {
        $this->run = false;
        $this->lockManager->unlock(self::LOCK_NAME);
    }
}

