<?php

use FriendsOfTYPO3\DeferredImageProcessing\Resource\Processing\DeferredFrontendImageProcessor;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['deferredFrontendImageProcessing'] ??= false;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['DeferredFrontendImageProcessor'] = [
    'className' => DeferredFrontendImageProcessor::class,
    'before' => ['LocalImageProcessor'],
    'after' => ['SvgImageProcessor']
];
