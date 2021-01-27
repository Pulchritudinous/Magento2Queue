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

namespace Pulchritudinous\Queue\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema
    implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $adapter    = $setup->getConnection();
        $tableName  = $setup->getTable('pulchritudinous_queue_labour');

        $table = $adapter
            ->newTable($tableName)
            ->addColumn(
                'id',
                Table::TYPE_BIGINT,
                20,
                [
                    'identity' => true,
                    'unsigned'  => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Entity ID'
            )
            ->addColumn(
                'parent_id',
                Table::TYPE_BIGINT,
                20,
                [
                    'unsigned'  => true,
                    'nullable'  => true,
                ],
                'Parent ID'
            )
            ->addColumn(
                'worker',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Worker Code'
            )
            ->addColumn(
                'identity',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Worker Identity'
            )
            ->addColumn(
                'priority',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned'  => true,
                    'nullable'  => false,
                    'default'   => '0',
                ],
                'Worker Priority'
            )
            ->addColumn(
                'payload',
                Table::TYPE_TEXT,
                '64k',
                [
                    'nullable'  => false,
                ],
                'Message'
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Status'
            )
            ->addColumn(
                'attempts',
                Table::TYPE_INTEGER,
                10,
                [
                    'unsigned'  => true,
                    'nullable'  => false,
                    'default'   => '0',
                ],
                'Attempt'
            )
           ->addColumn(
                'pid',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned'  => true,
                    'nullable'  => true,
                ],
                'PID'
           )

           ->addColumn(
                'by_recurring',
                Table::TYPE_SMALLINT,
                null,
                [
                    'unsigned'  => true,
                    'nullable'  => false,
                    'default'   => '0',
                ],
                'Is created by recurring'
            )
            ->addColumn(
                'execute_at',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable'  => false,
                    'unsigned'  => true,
                ],
                'Execute at'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable'  => false,
                    'unsigned'  => true,
                ],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable'  => false,
                    'unsigned'  => true,
                ],
                'Updated At'
            )
            ->addColumn(
                'started_at',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable'  => true,
                    'unsigned'  => true,
                ],
                'Updated At'
            )
            ->addColumn(
                'finished_at',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable'  => true,
                    'unsigned'  => true,
                ],
                'Updated At'
            )
            ->addIndex(
                $setup->getIdxName(
                    $tableName,
                    ['parent_id']
                ),
                ['parent_id']
            )
            ->addIndex(
                $setup->getIdxName(
                    $tableName,
                    ['worker']
                ),
                ['worker']
            )
            ->addIndex(
                $setup->getIdxName(
                    $tableName,
                    ['identity']
                ),
                ['identity']
            )
            ->addForeignKey(
                $setup->getFkName(
                    $tableName,
                    'parent_id',
                    $tableName,
                    'id'
                ),
                'parent_id',
                $tableName,
                'id',
                Table::ACTION_CASCADE,
                Table::ACTION_CASCADE
            )
            ->setComment('Worker Queue');

        if (!$adapter->isTableExists($tableName)) {
            $adapter->createTable($table);
        }

        $setup->endSetup();
    }
}

