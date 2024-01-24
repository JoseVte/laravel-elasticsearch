<?php

namespace MailerLite\LaravelElasticsearch\Tests;

use MailerLite\LaravelElasticsearch\Facade;
use Orchestra\Testbench\TestCase as Orchestra;
use MailerLite\LaravelElasticsearch\ServiceProvider;

/**
 * Class TestCase.
 */
abstract class TestCase extends Orchestra
{
    /**
     * @inheritdoc
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getPackageAliases($app)
    {
        return [
            'Elasticsearch' => Facade::class,
        ];
    }
}
