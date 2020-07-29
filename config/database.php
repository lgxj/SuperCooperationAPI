<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'sc_pool'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'sc_pool' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SC_POOL_HOST', '127.0.0.1'),
            'port' => env('DB_SC_POOL_PORT', '3306'),
            'database' => env('DB_SC_POOL_DATABASE', 'sc_pool'),
            'username' => env('DB_SC_POOL_USERNAME', 'root'),
            'password' => env('DB_SC_POOL_PASSWORD', 'root'),
            'unix_socket' => env('DB_SC_POOL_SOCKET', ''),
            'prefix' => env('DB_SC_POOL_PREFIX', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],


        'sc_trade' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SC_TRADE_HOST', '127.0.0.1'),
            'port' => env('DB_SC_TRADE_PORT', '3306'),
            'database' => env('DB_SC_TRADE_DATABASE', 'sc_trade'),
            'username' => env('DB_SC_TRADE_USERNAME', 'root'),
            'password' => env('DB_SC_TRADE_PASSWORD', 'root'),
            'unix_socket' => env('DB_SC_TRADE_SOCKET', ''),
            'prefix' => env('DB_SC_TRADE_PREFIX', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'sc_user' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SC_USER_HOST', '127.0.0.1'),
            'port' => env('DB_SC_USER_PORT', '3306'),
            'database' => env('DB_SC_USER_DATABASE', 'sc_user'),
            'username' => env('DB_SC_USER_USERNAME', 'root'),
            'password' => env('DB_SC_USER_PASSWORD', 'root'),
            'unix_socket' => env('DB_SC_USER_SOCKET', ''),
            'prefix' => env('DB_SC_USER_PREFIX', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'sc_permission' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SC_PERMISSION_HOST', '127.0.0.1'),
            'port' => env('DB_SC_PERMISSION_PORT', '3306'),
            'database' => env('DB_SC_PERMISSION_DATABASE', 'sc_permission'),
            'username' => env('DB_SC_PERMISSION_USERNAME', 'root'),
            'password' => env('DB_SC_PERMISSION_PASSWORD', 'root'),
            'unix_socket' => env('DB_SC_PERMISSION_SOCKET', ''),
            'prefix' => env('DB_SC_PERMISSION_PREFIX', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'sc_message' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SC_MESSAGE_HOST', '127.0.0.1'),
            'port' => env('DB_SC_MESSAGE_PORT', '3306'),
            'database' => env('DB_SC_MESSAGE_DATABASE', 'sc_message'),
            'username' => env('DB_SC_MESSAGE_USERNAME', 'root'),
            'password' => env('DB_SC_MESSAGE_PASSWORD', 'root'),
            'unix_socket' => env('DB_SC_MESSAGE_SOCKET', ''),
            'prefix' => env('DB_SC_MESSAGE_PREFIX', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'sc_statistics' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_SC_STATISTICS_HOST', '127.0.0.1'),
            'port' => env('DB_SC_STATISTICS_PORT', '3306'),
            'database' => env('DB_SC_STATISTICS_DATABASE', 'sc_statistics'),
            'username' => env('DB_SC_STATISTICS_USERNAME', 'root'),
            'password' => env('DB_SC_STATISTICS_PASSWORD', 'root'),
            'unix_socket' => env('DB_SC_STATISTICS_SOCKET', ''),
            'prefix' => env('DB_SC_STATISTICS_PREFIX', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_CACHE_HOST', '127.0.0.1'),
            'password' => env('REDIS_CACHE_PASSWORD', null),
            'port' => env('REDIS_CACHE_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

        'geo' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_GEO_HOST', '127.0.0.1'),
            'password' => env('REDIS_GEO_PASSWORD', null),
            'port' => env('REDIS_GEO_PORT', 6379),
            'database' => env('REDIS_GEO_DB', 2),
        ],

        'login' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_LOGIN_HOST', '127.0.0.1'),
            'password' => env('REDIS_LOGIN_PASSWORD', null),
            'port' => env('REDIS_LOGIN_PORT', 6379),
            'database' => env('REDIS_LOGIN_DB', 3),
        ],

        'event_queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_EVENT_HOST', '127.0.0.1'),
            'password' => env('REDIS_EVENT_PASSWORD', null),
            'port' => env('REDIS_EVENT_PORT', 6379),
            'database' => env('REDIS_EVENT_DB', 4),
        ],

        'job_queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_JOB_HOST', '127.0.0.1'),
            'password' => env('REDIS_JOB_PASSWORD', null),
            'port' => env('REDIS_JOB_PORT', 6379),
            'database' => env('REDIS_JOB_DB', 5),
        ],
    ],

];
