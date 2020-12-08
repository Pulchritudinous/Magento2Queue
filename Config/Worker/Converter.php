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

namespace Pulchritudinous\Queue\Config\Worker;

class Converter
    implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManager
     */
    protected $objectManager;

    /**
     * Appplication state
     *
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->appState = $appState;
        $this->objectManager = $objectManager;
    }

    /**
     * Convert DOM node tree to array
     *
     * @param \DOMDocument $source
     *
     * @return array
     */
    public function convert($source) : array
    {
        $output = ['workers' => [], 'default' => [], 'server_default' => []];
        $xpath = new \DOMXPath($source);
        $nodes = $xpath->evaluate('/config/queue/worker');
        $devMode = $this->isDeveloperMode();

        /** @var $node \DOMNode */
        foreach ($nodes as $node) {
            $nodeId = $node->attributes->getNamedItem('id')->value;

            $data = [];
            $data['code'] = $nodeId;

            $skip = false;

            foreach ($node->childNodes as $childNode) {
                if ($childNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                if ('rule' === $childNode->nodeName
                    && 'test' === $childNode->nodeValue
                    && false === $isDevMode
                ) {
                    $skip = true;
                }

                switch ($childNode->nodeName) {
                    case 'recurring':
                        $data[$childNode->nodeName] = $this->convertCronSchedule($childNode);
                        break;
                    default:
                        $data[$childNode->nodeName] = $childNode->nodeValue;
                        break;
                }
            }

            if (true === $skip) {
                continue;
            }

            $output['workers'][$nodeId] = $data;
        }

        $default = $xpath->query('/config/queue/worker_default')->item(0);

        foreach ($default->childNodes as $childNode) {
            if ($childNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }

            switch ($childNode->nodeName) {
                case 'recurring':
                    $output['default'][$childNode->nodeName] = $this->convertCronSchedule($childNode);
                    break;
                default:
                    $output['default'][$childNode->nodeName] = $childNode->nodeValue;
                    break;
            }
        }

        return $output;
    }

    /**
     * Convert schedule cron configurations
     *
     * @param \DOMElement $jobConfig
     * @return array
     */
    protected function convertCronSchedule(\DOMElement $jobConfig) : array
    {
        $result = [];
        /** @var \DOMText $schedules */
        foreach ($jobConfig->childNodes as $schedules) {
            if ('schedule' === $schedules->nodeName) {
                if (!empty($schedules->nodeValue)) {
                    $result['schedule'] = $schedules->nodeValue;
                }

                continue;
            }

            if ('is_allowed' === $schedules->nodeName) {
                if (!empty($schedules->nodeValue)) {
                    list($recClass, $recMethod) = explode('::', $schedules->nodeValue);

                    $obj = $this->objectManager->get($recClass);

                    if ($obj && true === method_exists($obj, $recMethod)) {
                        $result['is_allowed'] = (bool) \call_user_func(get_class($obj) . '::' . $recMethod);
                    }
                }

                continue;
            }
        }

        return $result;
    }

    /**
     * Developer mode
     *
     * @return bool
     */
    protected function isDeveloperMode() : bool
    {
        return $this->appState->getMode() == $this->appState::MODE_DEVELOPER;
    }
}

