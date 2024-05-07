<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\DeferredImageProcessing\Middleware;

use FriendsOfTYPO3\DeferredImageProcessing\Resource\Dto\QueuedTask;
use FriendsOfTYPO3\DeferredImageProcessing\Resource\ProcessedFileQueueRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\FileProcessingAspect;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class DeferredImageProcessing implements MiddlewareInterface
{
    public function __construct(private Features $features)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $inputFormat = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (
            !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $inputFormat)
            || !$this->features->isFeatureEnabled('deferredFrontendImageProcessing')
        ) {
            return $handler->handle($request);
        }

        /** @var ProcessedFileQueueRepository $processedFileQueueRepository */
        $processedFileQueueRepository = GeneralUtility::makeInstance(ProcessedFileQueueRepository::class);
        $queuedTask = $processedFileQueueRepository->findByPublicUrl($path);

        if ($queuedTask instanceof QueuedTask === false) {
            return $handler->handle($request);
        }

        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($queuedTask->getStorage());
        // set fileProcessing aspect to inform the file processor to not defer processing
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('fileProcessing', new FileProcessingAspect(false));

        $processedFile = $storage->processFile(
            GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($queuedTask->getOriginal()),
            $queuedTask->getTaskType(),
            $queuedTask->getConfiguration()
        );

        if ($processedFile->exists()) {
            $response = GeneralUtility::makeInstance(ResponseFactoryInterface::class)->createResponse();
            $processedFileQueueRepository->dequeue($queuedTask->getUid());
            $response = $response
                ->withStatus(200)
                ->withHeader('Content-type', $processedFile->getMimeType())
                ->withHeader('Content-length', (string)$processedFile->getSize());
            $response->getBody()->write($processedFile->getContents());

            return $response;
        }
    }
}
