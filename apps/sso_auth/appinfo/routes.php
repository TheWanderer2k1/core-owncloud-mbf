<?php

return [
    'routes' => [
        [
            'name' => 'config#index',
            'url'  => '/',
            'verb' => 'GET'
        ],
        [
            'name' => 'config#save',
            'url'  => '/save',
            'verb' => 'POST'
        ],
        [
            'name' => 'register#login',
            'url'  => '/login',
            'verb' => 'POST'
        ],
        [
            'name' => 'register#register',
            'url'  => '/register',
            'verb' => 'POST'
        ],
    ]
];