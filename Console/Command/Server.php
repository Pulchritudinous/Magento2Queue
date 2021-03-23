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

namespace Pulchritudinous\Queue\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Server extends Command
{
    const FLAG_CODE = 'pulchqueue';
    const LOCK_NAME = 'pulchqueue';
    const ARGUMENT_THREADS = 'threads';
    const ARGUMENT_POLL = 'poll';
    const ARGUMENT_PLAN_AHEAD = 'planahead';
    const ARGUMENT_RESOLUTION = 'resolution';

    /**
     * @var \Magento\Framework\Lock\LockManagerInterface
     */
    private $lockManager;

    /**
     * @var string
     */
    private $rootPath;

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
     * Array helper
     *
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrHelper;

    /**
     * Flag manager instance
     *
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    /**
     * Queue factory
     *
     * @var \Pulchritudinous\Queue\Helper\QueueFactory
     */
    protected $queueFactory;

    /**
     * Db helper instance
     *
     * @var \Pulchritudinous\Queue\Helper\Db
     */
    protected $dbHelper;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Symfony\Component\Process\PhpExecutableFinder
     */
    protected $phpExecutableFinder;

    /**
     * Should be running
     *
     * @var boolean
     */
    protected $run = true;

    /**
     * Server constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger,
     * @param \Magento\Framework\FlagManager $flagManager,
     * @param \Magento\Framework\Stdlib\ArrayManager $arrHelper,
     * @param \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
     * @param \Magento\Framework\Filesystem\DirectoryList $directory,
     * @param \Magento\Framework\Lock\LockManagerInterface $lockManager,
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory,
     * @param \Pulchritudinous\Queue\Helper\Db $dbHelper,
     * @param \Pulchritudinous\Queue\Helper\QueueFactory $queueFactory,
     * @param \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig,
     * @param \Pulchritudinous\Queue\Helper\Worker\Factory $workerFactory,
     * @param \Pulchritudinous\Queue\Console\Command\LabourFactory $labourCmdFactory,
     * @param string $name
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\FlagManager $flagManager,
        \Magento\Framework\Stdlib\ArrayManager $arrHelper,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Framework\Lock\LockManagerInterface $lockManager,
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory,
        \Pulchritudinous\Queue\Helper\Db $dbHelper,
        \Pulchritudinous\Queue\Helper\QueueFactory $queueFactory,
        \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig,
        \Pulchritudinous\Queue\Helper\Worker\Factory $workerFactory,
        \Pulchritudinous\Queue\Console\Command\LabourFactory $labourCmdFactory,
        $name = null
    ) {
        $this->lockManager = $lockManager;
        $this->workerConfig = $workerConfig;
        $this->workerFactory = $workerFactory;
        $this->queueFactory = $queueFactory;
        $this->dbHelper = $dbHelper;
        $this->arrHelper = $arrHelper;
        $this->flagManager = $flagManager;
        $this->logger = $logger;
        $this->labourCmdFactory = $labourCmdFactory;
        $this->scheduleFactory = $scheduleFactory;

        $this->rootPath = $directory->getRoot();
        $this->phpExecutableFinder = $phpExecutableFinderFactory->create();

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pulchqueue:server');
        $this->setDescription('Start queue server');

        $this->setDefinition(
            new InputDefinition([
                new InputOption(self::ARGUMENT_THREADS, 't', InputOption::VALUE_OPTIONAL),
                new InputOption(self::ARGUMENT_POLL, 'p', InputOption::VALUE_OPTIONAL),
                new InputOption(self::ARGUMENT_PLAN_AHEAD, 'a', InputOption::VALUE_OPTIONAL),
                new InputOption(self::ARGUMENT_RESOLUTION, 'r', InputOption::VALUE_OPTIONAL),
            ])
        );

        $this->addArgument(self::ARGUMENT_THREADS, InputArgument::OPTIONAL, __('How many simultaneous threads?'));
        $this->addArgument(self::ARGUMENT_POLL, InputArgument::OPTIONAL, __('How often to look for executable labours (sec)?'));
        $this->addArgument(self::ARGUMENT_PLAN_AHEAD, InputArgument::OPTIONAL, __('Recurring - Plan minutes ahead?'));
        $this->addArgument(self::ARGUMENT_RESOLUTION, InputArgument::OPTIONAL, __('Recurring - Resolution?'));

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $threads = (int) ($input->getOption(self::ARGUMENT_THREADS) ?: 2);
        $poll = (int) ($input->getOption(self::ARGUMENT_POLL) ?: 2);
        $planAhead = (int) ($input->getOption(self::ARGUMENT_PLAN_AHEAD) ?: 10);
        $resolution = (int) ($input->getOption(self::ARGUMENT_RESOLUTION) ?: 1);

        if ($threads < 1) {
            $output->writeln("<error>threads must be greater than 0</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if ($poll < 1) {
            $output->writeln("<error>poll must be greater than 0</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if ($planAhead < 1) {
            $output->writeln("<error>plan ahead must be greater than 0</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if ($resolution < 1) {
            $output->writeln("<error>resolution must be greater than 0</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $queue = $this->queueFactory->create();

        $queue->beforeServerStart();

        $this->_updateLastSchedule();

        try {
            if (false === $this->lockManager->lock(md5(self::LOCK_NAME), 5)) {
                throw new \Exception('Queue is already running');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Queue is already running</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln('Server started');

        $processes = [];

        try {
            while ($this->run) {
                $processes = $this->_validateProcesses($processes);

                $this->addRecurringLabours($output, $planAhead, $resolution);

                if (!$this->_canStartNext(count($processes), $threads)) {
                    sleep($poll);
                    continue;
                }

                $labours = $queue->receive($this->_canReceiveCount(count($processes), $threads));

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

                    try {
                        $process = $this->_getProcess($labour);

                        $process->start();

                        if (!$process->isRunning()) {
                            throw new ProcessFailedException($process);
                        }

                        $this->dbHelper->updateLabourField($labour, 'pid', (string) $process->getPid());

                        $processes[] = $process;
                    } catch (\Throwable $e) {
                        $this->logger->critical($e);
                        $msg = $e->getMessage();
                        $output->writeln("<error>$msg</error>");

                        $labour->reschedule();
                    }
                }
            }
        } catch (\Throwable $e) {
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
     * @param array $processes
     *
     * @return array
     */
    protected function _validateProcesses(array $processes) : array
    {
        foreach ($processes as $i => $process) {
            if (!$this->_isProcessRunning($process)) {
                $code = $process->getExitCode();

                if (255 === $code) {
                    $process->getLabour()->reschedule();
                }

                unset($processes[$i]);
            }
        }

        return $processes;
    }

    /**
     * Validate single process.
     *
     * @param Process $process
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
     * @param \Pulchritudinous\Queue\Model\Labour $labour
     *
     * @return Process
     */
    protected function _getProcess(\Pulchritudinous\Queue\Model\Labour $labour) : \Pulchritudinous\Queue\Model\Server\Process
    {
        $config = $this->workerConfig->getWorkerConfigById($labour->getWorker());
        $labourCmd = $this->labourCmdFactory->create();

        $labourCmd->configure();

        $php = $this->phpExecutableFinder->find() ?: 'php';
        $magentoBinary = BP . '/bin/magento';
        $labourCmdName = $labourCmd->getName();
        $arguments = $labour->getId();

        $process = (new Process([
                $php,
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
     * Check if another process is allowed to start.
     *
     * @param int $processCount
     * @param int $threads
     *
     * @return boolean
     */
    protected function _canStartNext(int $processCount, int $threads) : bool
    {
        if (0 !== $this->_canReceiveCount($processCount, $threads)) {
            return true;
        }
        return false;
    }

    /**
     * Can receive number of labours.
     *
     * @param int $processCount
     *
     * @return int
     */
    protected function _canReceiveCount(int $processCount, int $threads) : int
    {
        return max(0, $threads - $processCount);
    }

    /**
     * Add recurring labours to queue.
     *
     * @param OutputInterface $output
     * @param int $planAhead
     * @param int $resolution
     *
     * @return Server
     */
    public function addRecurringLabours(OutputInterface $output, int $planAhead, int $resolution) : Server
    {
        $workers = $this->workerConfig->getRecurringWorkers();
        $last = (int) $this->_lastSchedule;
        $itsTime = ($last + $planAhead * 60) <= time();

        if (!$itsTime) {
            return $this;
        }

        $output->writeln('Scheduling recurring labors');

        $queue = $this->queueFactory->create();
        $count = 0;

        $this->_updateLastSchedule(time());

        foreach ($workers as $worker) {
            $isAllowed = $this->arrHelper->get('recurring/is_allowed', $worker, true);

            if (false === $isAllowed) {
                continue;
            }

            $pattern = $this->arrHelper->get('recurring/schedule', $worker);
            $runTimes = $this->generateRunDates($pattern, $planAhead, $resolution);

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
                    $queue->add(
                        $worker['code'],
                        $payload,
                        $options
                    );

                    $count++;
                } catch (\Throwable $e) {
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
     * @param null|int $time
     *
     * @return Server
     */
    protected function _updateLastSchedule(int $time = null) : Server
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
     * @param string $pattern
     * @param int $planAhead
     * @param int $resolution
     *
     * @return array
     */
    public function generateRunDates(string $pattern, int $planAhead, int $resolution) : array
    {
        $scheduler = $this->scheduleFactory->create();
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

