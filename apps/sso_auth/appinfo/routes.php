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
            'name' => 'register#index',
            'url'  => '/register',
            'verb' => 'GET'
        ],
        [
            'name' => 'register#login',
            'url'  => '/register/login',
            'verb' => 'POST'
        ],
        [
            'name' => 'register#register',
            'url'  => '/register/create',
            'verb' => 'POST'
        ],
    ]
];