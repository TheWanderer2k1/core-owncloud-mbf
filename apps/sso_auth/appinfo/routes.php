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
        [
            'name' => 'register#registerSMS',
            'url'  => '/internal/register/sms',
            'verb' => 'POST'
        ],
        // tmp route for delete account just to pass ios review, will be removed in future
        [
            'name' => 'account#delete',
            'url'  => '/account/delete',
            'verb' => 'POST'
        ],
    ]
];