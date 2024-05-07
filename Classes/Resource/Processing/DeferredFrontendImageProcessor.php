<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\DeferredImageProcessing\Resource\Processing;

use FriendsOfTYPO3\DeferredImageProcessing\Resource\ProcessedFileQueueRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeferredFrontendImageProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Features $features,
        private readonly ProcessedFileQueueRepository $processedFileQueueRepository
    ) {
    }

    public function canProcessTask(TaskInterface $task): bool
    {
        return $this->features->isFeatureEnabled('deferredFrontendImageProcessing')
            && ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            && $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true);
    }

    public function processTask(TaskInterface $task): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        // it will be set to false by the DeferredImageProcessing middleware
        // to trigger the processing of the image requested by the frontend
        $isDeferredExecutionEnabled = !$context->hasAspect('fileProcessing') || !$context->getPropertyFromAspect('fileProcessing', 'deferProcessing');

        if (!$isDeferredExecutionEnabled) {
            // in the final implementation, maybe handle different processors here as they are configured
            /** @var LocalImageProcessor $localImageProcessor */
            $localImageProcessor = GeneralUtility::makeInstance(LocalImageProcessor::class);
            $localImageProcessor->processTask($task);
        }

        if (!$this->processedFileQueueRepository->isEnqueued($task)) {
            if (!$task->getTargetFile()->isPersisted()) {
                $task->getTargetFile()->setName($task->getTargetFileName());
            }
            $this->processedFileQueueRepository->enqueue($task);
        }

        $task->setExecuted(true);
    }
}
