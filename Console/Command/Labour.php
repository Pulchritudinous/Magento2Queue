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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Pulchritudinous\Queue\Model\Labour as LabourModel;

class Labour extends Command
{
    const LABOUR_ARGUMENT = 'labour';

    /**
     * Labour instance
     *
     * @var \Pulchritudinous\Queue\Model\Labour
     */
    protected $labourModel = null;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Server constructor.
     *
     * @param string $name
     */
    public function __construct(
        $name = null,
        LoggerInterface $labourModel = null,
        LabourModel $logger = null
    ) {
        $objectManager = ObjectManager::getInstance();

        $this->labourModel = $labourModel ?: $objectManager->get(LabourModel::class);
        $this->logger = $logger ?: $objectManager->get(LoggerInterface::class);

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pulchqueue:labour');
        $this->setDescription('Run labour');
        $this->setDefinition([
            new InputArgument(self::LABOUR_ARGUMENT, InputArgument::OPTIONAL, 'Labour'),
        ]);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!($labourId = $input->getArgument(self::LABOUR_ARGUMENT))) {
            $output->writeln('<error>No labour ID specified</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        try {
            $labour = $this->labourModel->load($labourId);

            if (!$labour->getId()) {
                throw new \Exception('Unable to find labour');
            }

            $labour->execute();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $msg = $e->getMessage();
            $output->writeln("<error>$msg</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}

