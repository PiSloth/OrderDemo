<?php

return [
    'driver' => env('SCOUT_DRIVER', 'meilisearch'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue' => env('SCOUT_QUEUE', true),
    'after_commit' => false,
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    'soft_delete' => true,
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'company_documents' => [
                'searchableAttributes' => [
                    'title',
                    'category',
                    'department',
                    'creator',
                    'content_text',
                ],
                'filterableAttributes' => [
                    'department_id',
                    'category_id',
                    'creator_id',
                    'is_announcement',
                    'published_at',
                ],
                'sortableAttributes' => [
                    'published_at',
                    'updated_at',
                    'title',
                ],
            ],
        ],
    ],
];

