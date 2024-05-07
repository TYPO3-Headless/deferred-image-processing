<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\DeferredImageProcessing\Tests\Unit\Resource\Dto;

use FriendsOfTYPO3\DeferredImageProcessing\Resource\Dto\QueuedTask;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;

class QueuedTaskTest extends TestCase
{
    private QueuedTask $queuedTask;

    protected function setUp(): void
    {
        $this->queuedTask = new QueuedTask(
            uid: 1,
            publicUrl: '/fileadmin/_processed_/9/4/csm_test-image-_c8e3cc953f.png',
            storage: 1,
            original: 2,
            taskType: 'Image.CropScaleMask',
            configuration: 'a:3:{s:5:"width";i:600;s:6:"height";i:472;s:4:"crop";O:45:"TYPO3\CMS\Core\Imaging\ImageManipulation\Area":4:{s:4:" * x";d:522.24;s:4:" * y";d:282.24;s:8:" * width";d:1031.04;s:9:" * height";d:812.16;}}',
            checksum: 'c8e3cc953f'
        );
    }

    public function testGetUid(): void
    {
        $this->assertSame(1, $this->queuedTask->getUid());
    }

    public function testGetPublicUrl(): void
    {
        $this->assertSame('/fileadmin/_processed_/9/4/csm_test-image-_c8e3cc953f.png', $this->queuedTask->getPublicUrl());
    }

    public function testGetStorage(): void
    {
        $this->assertSame(1, $this->queuedTask->getStorage());
    }

    public function testGetOriginal(): void
    {
        $this->assertSame(2, $this->queuedTask->getOriginal());
    }

    public function testGetTaskType(): void
    {
        $this->assertSame('Image.CropScaleMask', $this->queuedTask->getTaskType());
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->queuedTask->getConfiguration();

        $this->assertIsArray($configuration);
        $this->assertSame(600, $configuration['width']);
        $this->assertSame(472, $configuration['height']);
        $this->assertInstanceOf(Area::class, $configuration['crop']);
    }

    public function testGetChecksum(): void
    {
        $this->assertSame('c8e3cc953f', $this->queuedTask->getChecksum());
    }
}
