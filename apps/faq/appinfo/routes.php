<?php

return [
    'routes' => [
        [
            'name' => 'faq#index',
            'url'  => '/',
            'verb' => 'GET'
        ],
        [
            'name' => 'faq#create',
            'url'  => '/create',
            'verb' => 'POST'
        ],
        [
            'name' => 'faq#update',
            'url'  => '/update',
            'verb' => 'POST'
        ],
        [
            'name' => 'faq#delete',
            'url'  => '/delete',
            'verb' => 'POST'
        ],
        [
            'name' => 'faq#publish',
            'url'  => '/publish',
            'verb' => 'GET'
        ],
    ]
];
