<?php

// namespace YourCompany\GraphQLDAL\Tests;

// use Orchestra\Testbench\TestCase as Orchestra;
// use YourCompany\GraphQLDAL\Providers\GraphQLDALServiceProvider;

// abstract class TestCase extends Orchestra
// {
//     protected function setUp(): void
//     {
//         parent::setUp();
//     }

//     protected function getPackageProviders($app)
//     {
//         return [
//             GraphQLDALServiceProvider::class,
//         ];
//     }

//     protected function getEnvironmentSetUp($app)
//     {
//         // Setup default database to use sqlite :memory:
//         $app['config']->set('database.default', 'testing');
//         $app['config']->set('database.connections.testing', [
//             'driver' => 'sqlite',
//             'database' => ':memory:',
//             'prefix' => '',
//         ]);

//         // Setup GraphQL DAL configuration
//         $app['config']->set('graphql-dal', [
//             'default_connection' => 'testing',
//             'transactions' => [
//                 'auto_commit' => true,
//                 'max_retries' => 3,
//                 'retry_delay' => 1000,
//             ],
//             'repositories' => [
//                 'cache_enabled' => false,
//                 'cache_ttl' => 3600,
//             ],
//         ]);
//     }
// }