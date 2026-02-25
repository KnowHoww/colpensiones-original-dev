<?php

return [

    'default' => 'azure',

    'disks' => [
        

        'azure' => [
            'driver' => 'azure-blob',
            'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING'),
            'container' => env('AZURE_STORAGE_CONTAINER'),
            'url' => env('AZURE_STORAGE_URL'),
            'throw' => true,
        ],

    ],

];