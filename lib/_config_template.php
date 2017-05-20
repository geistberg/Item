<?php

class Config {

    public $memcache = [ 
        'server' => 'localhost',
        'port'   => 11211,
    ];

    public $database = [
        'write'     => [
            'hostname' => 'localhost',
            'username' => 'username',
            'password' => 'password',
            'database' => 'database',
            'driver'   => 'pgsql',
            'port'     => '5432',
        ],
        'read_only' => [
            'hostname' => 'localhost',
            'username' => 'username',
            'password' => 'password',
            'database' => 'database',
            'driver'   => 'pgsql',
            'port'     => '5432',
        ]
    ];
}

?>
