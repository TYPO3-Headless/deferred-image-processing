<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Deferred Image Processing for Frontend',
    'description' => 'This extension provides a way to defer image processing to a later time, when the image is actually requested by the frontend user.',
    'category' => 'example',
    'author' => 'Macopedia Devs',
    'author_email' => 'dev@macopedia.com',
    'author_company' => 'Macopedia',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.2.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
