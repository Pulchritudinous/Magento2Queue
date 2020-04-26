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

class Config
{
    /**
     * Reader
     *
     * @var \Pulchritudinous\Queue\Config\Worker\Reader
     */
    private $xmlReader;

    /**
     * Array helper
     *
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrHelper;

    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManager
     */
    private $objectManager;

    /*
     * Worker configuration
     *
     * @var array
     */
    private $config = [];

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Pulchritudinous\Queue\Config\Worker\Reader $reader,
        \Magento\Framework\Stdlib\ArrayManager $arrHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->xmlReader = $reader;
        $this->arrHelper = $arrHelper;
        $this->config = $reader->read();
        $this->objectManager = $objectManager;
    }

    /**
     * Returns default worker configuration.
     *
     * @return array
     */
    public function getWorkerDefaultConfig() : array
    {
        return $this->arrHelper->get('default', $this->config, []);
    }

    /**
     * Returns worker configuration.
     *
     * @param  string $id
     *
     * @return null|array
     */
    public function getWorkerConfigById(string $id) :? array
    {
        $id = strtolower($id);

        if (!($config = $this->arrHelper->get("workers/{$id}", $this->config, false))) {
            return null;
        }

        $config = array_merge(
            $this->getWorkerDefaultConfig(),
            $config
        );

        return $config;
    }

    /**
     * Returns recurring workers.
     *
     * @return array
     */
    public function getRecurringWorkers() : array
    {
        if (!($workers = $this->arrHelper->get('workers', $this->config, []))) {
            return [];
        }

        $collection = [];

        foreach ($workers as $worker) {
            if (!($this->arrHelper->get('recurring', $worker, false))) {
                continue;
            }

            $collection[] = $worker;
        }

        return $collection;
    }
}

