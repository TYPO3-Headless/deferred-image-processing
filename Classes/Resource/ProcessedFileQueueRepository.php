<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\DeferredImageProcessing\Resource;

use FriendsOfTYPO3\DeferredImageProcessing\Resource\Dto\QueuedTask;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessedFileQueueRepository implements SingletonInterface
{
    private const TABLE = 'sys_file_processedfile_queue';
    private array $insertQueue = [];

    public function __destruct()
    {
        $this->persistQueue();
    }

    public function enqueue(TaskInterface $task): void
    {
        $this->insertQueue[$task->getConfigurationChecksum()] = [
            'public_url'    => $task->getTargetFile()->getPublicUrl(),
            'storage'       => $task->getSourceFile()->getStorage()->getUid(),
            'original'      => $task->getSourceFile()->getUid(),
            'task_type'     => $task->getType() . '.' . $task->getName(),
            'configuration' => (new ConfigurationService())->serialize($task->getConfiguration()),
            'checksum'      => $task->getConfigurationChecksum()
        ];
    }

    public function dequeue(int $uid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $connection->delete(
            self::TABLE,
            ['uid' => $uid]
        );
    }

    public function isEnqueued(TaskInterface $task): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);

        return isset($this->insertQueue[$task->getConfigurationChecksum()]) || $connection->count(
                '*',
                self::TABLE,
                [
                    'storage'   => $task->getSourceFile()->getStorage()->getUid(),
                    'original'  => $task->getSourceFile()->getUid(),
                    'task_type' => $task->getType() . '.' . $task->getName(),
                    'checksum'  => $task->getConfigurationChecksum()
                ],
            );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function findByPublicUrl(string $publicUrl): QueuedTask|bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $result = $connection
            ->select(
                ['*'],
                self::TABLE,
                ['public_url' => $publicUrl]
            )
            ->fetchAssociative();

        if (!$result) {
            return false;
        }

        return new QueuedTask(
            $result['uid'],
            $result['public_url'],
            $result['storage'],
            $result['original'],
            $result['task_type'],
            $result['configuration'],
            $result['checksum']
        );
    }

    private function persistQueue(): void
    {
        if (count($this->insertQueue) > 0) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
            $connection->bulkInsert(
                self::TABLE,
                $this->insertQueue,
                [
                    'public_url',
                    'storage',
                    'original',
                    'task_type',
                    'configuration',
                    'checksum',
                ],
                [
                    'storage'       => Connection::PARAM_INT,
                    'original'      => Connection::PARAM_INT,
                    'configuration' => Connection::PARAM_LOB,
                ]
            );
        }
    }
}
