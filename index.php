<?php

require_once __DIR__ . '/src/GitStats.php';

use Lemmon\Gitstats\GitStats;

Kirby::plugin('lemmon/gitstats', [
    'options' => [
        'cache' => [
            'active' => true,
            'type' => 'file',
        ],
        'cacheTtlLower' => GitStats::CACHE_DEFAULT_LOWER_TTL,
        'cacheTtlUpper' => GitStats::CACHE_DEFAULT_UPPER_TTL,
    ],
    'fieldMethods' => [
        'toGitStats' => function ($field) {
            return GitStats::fetch((string) $field->value());
        },
    ],
]);
