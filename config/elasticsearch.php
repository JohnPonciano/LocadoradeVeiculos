<?php

return [
    'host' => env('ELASTICSEARCH_HOST', 'elasticsearch'),
    'port' => env('ELASTICSEARCH_PORT', 9200),
    'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
    'indices' => [
        'vehicles' => [
            'mappings' => [
                'properties' => [
                    'plate' => [
                        'type' => 'keyword',
                        'fields' => [
                            'text' => [
                                'type' => 'text',
                                'analyzer' => 'standard'
                            ]
                        ]
                    ],
                    'make' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ],
                        'analyzer' => 'standard'
                    ],
                    'model' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ],
                        'analyzer' => 'standard'
                    ],
                    'daily_rate' => [
                        'type' => 'float'
                    ],
                    'available' => [
                        'type' => 'boolean'
                    ],
                    'created_at' => [
                        'type' => 'date'
                    ],
                    'updated_at' => [
                        'type' => 'date'
                    ]
                ]
            ],
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'custom_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'asciifolding']
                        ]
                    ]
                ]
            ]
        ],
        'customers' => [
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    'email' => ['type' => 'keyword'],
                    'phone' => ['type' => 'keyword'],
                    'cnh' => ['type' => 'keyword'],
                ]
            ]
        ]
    ]
]; 