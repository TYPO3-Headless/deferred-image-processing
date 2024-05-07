<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\DeferredImageProcessing\Resource\Dto;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;

readonly class QueuedTask
{
    public function __construct(
        private int $uid,
        private string $publicUrl,
        private int $storage,
        private int $original,
        private string $taskType,
        private string $configuration,
        private string $checksum
    ) {
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getStorage(): int
    {
        return $this->storage;
    }

    public function getOriginal(): int
    {
        return $this->original;
    }

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function getConfiguration(): array
    {
        return unserialize($this->configuration, ['allowed_classes' => [Area::class]]) ?? [];
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
