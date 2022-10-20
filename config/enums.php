<?php

return [
    
    'admin'     => [
        'name' => 'admin',
        'roles' => ['start', 'stop', 'clear', 'grant', 'revoke', 'deposit', 'issued', 'rate'],
    ],
    'operator'  => [
        'name' => 'operator',
        'roles' => ['deposit', 'issued']
    ],
    'guest'     => [
        'name' => 'guest',
        'roles' => ['seen']
    ],

    'triggers' => [
        'start'             => '/^(开始)$/',
        'stop'              => '/^(结束)$/',
        'grant'             => '/(?<=^设置操作人)\s*\X+$/',
        'revoke'            => '/(?<=^删除操作人)\s*\X+$/',
        'rate'              => '/(?<=^设置费率)\s?(?P<rate>\d+(?:\.\d+)?%)$/',
        'deposit'           => '/^入款\s?(?P<amount>-?\d+?)$/',
        'deposit_short'     => '/^\+(?P<amount>\d+?(?:\.\d+?)?)$/',
        'issued'            => '/^下发\s?(?P<amount>-?\d+?)$/',
        'issued_short'      => '/^\-(?P<amount>\d+?(?:\.\d+?)?)$/',
    ],

    'callback' => [
        'start'             => 'startRecords',
        'stop'              => 'stopRecords',
        'grant'             => 'grantRoles',
        'revoke'            => 'revokeRoles',
        'rate'              => 'rateHandle',
        'deposit'           => 'depositHandle',
        'deposit_short'     => 'depositHandle',
        'issued'            => 'issuedHandle',
        'issued_short'      => 'issuedHandle',
    ]

];