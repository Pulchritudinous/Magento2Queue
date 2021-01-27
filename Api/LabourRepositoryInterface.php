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

namespace Pulchritudinous\Queue\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface LabourRepositoryInterface
{
    /**
     * Save Labour
     *
     * @param \Pulchritudinous\Queue\Api\Data\LabourInterface $labour
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     */
    public function save(
        \Pulchritudinous\Queue\Api\Data\LabourInterface $labour
    ) : \Pulchritudinous\Queue\Api\Data\LabourInterface ;

    /**
     * Retrieve Labour
     *
     * @param int $labourId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourInterface
     *
     */
    public function get(int $labourId) : \Pulchritudinous\Queue\Api\Data\LabourInterface;

    /**
     * Retrieve Labour matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Pulchritudinous\Queue\Api\Data\LabourSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) : \Pulchritudinous\Queue\Api\Data\LabourSearchResultsInterface;

    /**
     * Delete Labour
     *
     * @param \Pulchritudinous\Queue\Api\Data\LabourInterface $labour
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return bool true on success
     */
    public function delete(
        \Pulchritudinous\Queue\Api\Data\LabourInterface $labour
    )  : bool;

    /**
     * Delete Labour by ID
     *
     * @param int $labourId
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return bool true on success
     */
    public function deleteById(int $labourId);
}

