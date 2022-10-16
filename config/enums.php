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
        'clear'             => '/^(clear)^/',
        'grant'             => '/(?<=^设置操作人)\s*(?P<user>\X+)$/',
        'revoke'            => '/(?<=^删除操作人)\s*(?P<user>\X+)$/',
        'deposit'           => '/^入款\s?(?P<amount>-?\d+?)$/',
        'deposit_short'     => '/^\+(?P<amount>\d+?(?:\.\d+?)?)$/',
        'issued'            => '/^下发\s?(?P<amount>-?\d+?)$/',
        'issued_short'      => '/^\-(?P<amount>\d+?(?:\.\d+?)?)$/',
        'rate'              => '/(?<=^设置费率)\s?(?P<rate>\d+(?:\.\d+)?%)$/',
    ]

];