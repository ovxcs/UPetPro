<?php

global $CONFIGS_DEFS;

$CONFIGS_DEFS = [
    [
        'name' => 'stoma',
        'regex' => [
            '/.{3,}\:8083$/'
        ],
        'script' => 'stoma.php' 
    ],
    
    [
        'name' => 'aucts',
        'regex' => [
            '/.{3,}\:8085$/'
        ],
        'script' => 'aucts.php'
    ]
]
?>