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

namespace Pulchritudinous\Queue\Helper\Worker;

use Magento\Framework\Stdlib\ArrayManager;

use Pulchritudinous\Queue\Model\WorkerInterface;

class Factory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Worker config instance
     *
     * @var \Pulchritudinous\Queue\Helper\Worker
     */
    protected $workerConfig = null;

    /**
     * Array helper
     *
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrHelper = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Stdlib\ArrayManager $arrHelper
     * @param \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\ArrayManager $arrHelper,
        \Pulchritudinous\Queue\Helper\Worker\Config $workerConfig
    ) {
        $this->objectManager = $objectManager;
        $this->arrHelper = $arrHelper;
        $this->workerConfig = $workerConfig;
    }

    /**
     * Load worker forge.
     *
     * @param Pulchritudinous\Queue\Model\Labour $labour
     *
     * @return null|WorkerInterface
     */
    public function create(\Pulchritudinous\Queue\Model\Labour $labour) :? WorkerInterface
    {
        $config = $labour->getWorkerConfig();
        $forge = $this->arrHelper->get('forge', $config);

        $this->objectManager->configure([
            $forge => [
                'arguments' => [
                    'labour' => null,
                ]
            ]
        ]);

        $object = $this->objectManager->create($forge, [
            'labour' => $labour,
        ]);

        if (!$object || !($object instanceof WorkerInterface)) {
            return null;
        }

        return $object;
    }

    /**
     * Load worker forge.
     *
     * @param string $worker
     *
     * @return null|WorkerInterface
     */
    public function createById(string $worker) :? WorkerInterface
    {
        $config = $this->workerConfig->getWorkerConfigById($worker);
        $forge = $this->arrHelper->get('forge', $config);
        $object = $this->objectManager->create($forge);

        if (!$object || !($object instanceof WorkerInterface)) {
            return null;
        }

        return $object;
    }
}

