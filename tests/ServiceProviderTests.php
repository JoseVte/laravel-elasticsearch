<?php

namespace MailerLite\LaravelElasticsearch\Tests;

use Elasticsearch;
use Elastic\Elasticsearch\Client;
use MailerLite\LaravelElasticsearch\Factory;
use MailerLite\LaravelElasticsearch\Manager;

class ServiceProviderTests extends TestCase
{
    public function testAbstractsAreLoaded(): void
    {
        $factory = app('elasticsearch.factory');
        $this->assertInstanceOf(Factory::class, $factory);

        $manager = app('elasticsearch');
        $this->assertInstanceOf(Manager::class, $manager);

        $client = app(Client::class);
        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * Test that the facade works.
     *
     * @todo This seems a bit simplistic ... maybe a better way to do this?
     */
    public function testFacadeWorks(): void
    {
        $ping = Elasticsearch::ping();

        $this->assertTrue($ping->asBool());
    }

    /**
     * Test we can get the ES info.
     */
    public function testInfoArrayWorks(): void
    {
        $info = Elasticsearch::info()->asArray();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('cluster_name', $info);
    }

    /**
     * Test we can get the ES info.
     */
    public function testInfoObjectWorks(): void
    {
        $info = Elasticsearch::info()->asObject();

        $this->assertIsObject($info);
        $this->assertObjectHasProperty('cluster_name', $info);
    }
}
