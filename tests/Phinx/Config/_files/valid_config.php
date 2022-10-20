<?php
return [
    'paths' => [
        'migrations' => [
            'application/migrations',
            'application2/migrations'
        ],
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => 'dev',
        'dev' => [
            'adapter' => 'mysql',
            'wrapper' => 'testwrapper',
            'host' => 'localhost',
            'name' => 'testing',
            'user' => 'root',
            'pass' => '',
            'port' => 3306
        ]
    ]
];
