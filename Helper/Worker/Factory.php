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
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Config $workerConfig,
        ArrayManager $arrHelper = null
    ) {
        $this->objectManager = $objectManager;
        $this->arrHelper = $arrHelper ?: $objectManager->get(ArrayManager::class);
        $this->workerConfig = $workerConfig;
    }

    /**
     * Load worker forge.
     *
     * @param null|Pulchritudinous\Queue\Model\Labour $labour
     * @param LabourCollection|null $children
     *
     * @return null|WorkerInterface
     */
    public function create(\Pulchritudinous\Queue\Model\Labour $labour,
        LabourCollection $children = null
    ) :? WorkerInterface
    {
        $config = $this->workerConfig->getWorkerConfigById($labour->getWorker());
        $forge = $this->arrHelper->get('forge', $config);

        $this->objectManager->configure([
            $forge => [
                'arguments' => [
                    'config'    => array_fill_keys(array_keys($config), null),
                    'labour'    => [],
                    'children'  => null,
                ]
            ]
        ]);

        $object = $this->objectManager->create($forge, [
            'config'    => $config,
            'labour'    => $labour,
            'children'  => $children,
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
        $forge  = $this->arrHelper->get('forge', $config);

        $this->objectManager->configure([
            $forge => [
                'arguments' => [
                    'config' => array_fill_keys(array_keys($config), null),
                ]
            ]
        ]);

        $object = $this->objectManager->create($forge, [
            'config' => $config,
        ]);

        if (!$object || !($object instanceof WorkerInterface)) {
            return null;
        }

        return $object;
    }
}

