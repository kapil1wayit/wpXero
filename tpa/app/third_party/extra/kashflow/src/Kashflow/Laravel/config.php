<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kashflow API Config
    |--------------------------------------------------------------------------
    |
    | Configure the Kashflow SOAP Wrapper below.
    | Use the debug option to test your integration.
    | Use the database settings to fetch tokens from db not config.
    |
    */

    'tokens' => [
        'username' => 'your-username',
        'password' => 'your-password',
    ],
    
    'debug'   => false,

    /*
     * Database link expects a table that has key:value style access.
     * Enter the name of the table to lookup.
     * Enter the index column that should be searched.
     * Enter the value column that data should be taken from.
     *
     * e.g. Settings Table
     *
     *   --------------------------------------------------------------------
     *   | id   | index                 | value                             |
     *   --------------------------------------------------------------------
     *   | 1    | api_tokens_consumer   | xxxxxxx-xxxxxxx-xxxxxxx-xxxxxxx   |
     *   --------------------------------------------------------------------
     *     
     */
    'database' => [
        'active' => false,     // set to true to enable db access
        'table'  => 'settings',
        'index'  => 'index',
        'value'  => 'value',
    ],

];
