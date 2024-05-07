<?php
return [
    'frontend' => [
        'friendsoftypo3/deferred-image-processing' => [
            'target' => FriendsOfTYPO3\DeferredImageProcessing\Middleware\DeferredImageProcessing::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
