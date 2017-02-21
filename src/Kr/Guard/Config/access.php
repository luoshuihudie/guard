<?php 

return [
    'logger' => [
        'handle' => 'access.handle', // 
        // 'handle' => function($router, $key, $log){
        //     logger("$router $key $log");
        // }
    ],
    'throttle' => [
        'total' => 800,
        'decay' => 60 * 24,
        'message' => '',
        'trigger' => 'throttle.handle',
    ]
];