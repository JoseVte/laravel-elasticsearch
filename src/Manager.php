<?php

namespace MailerLite\LaravelElasticsearch;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Elastic\Elasticsearch\Client;
use Illuminate\Contracts\Container\Container;
use Elastic\Elasticsearch\Exception\AuthenticationException;

/**
 * Class Manager.
 */
class Manager
{
    /**
     * The application instance.
     *
     * @var Container
     */
    protected $app;

    /**
     * The Elasticsearch connection factory instance.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * @param Container $app
     * @param Factory   $factory
     */
    public function __construct(Container $app, Factory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Retrieve or build the named connection.
     *
     * @param string|null $name
     *
     * @throws AuthenticationException
     *
     * @return Client
     */
    public function connection(string $name = null): Client
    {
        $name = $name ?: $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $client = $this->makeConnection($name);

            $this->connections[$name] = $client;
        }

        return $this->connections[$name];
    }

    /**
     * Get the default connection.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->app['config']['elasticsearch.defaultConnection'];
    }

    /**
     * Set the default connection.
     *
     * @param string $connection
     */
    public function setDefaultConnection(string $connection): void
    {
        $this->app['config']['elasticsearch.defaultConnection'] = $connection;
    }

    /**
     * Make a new connection.
     *
     * @param string $name
     *
     * @throws AuthenticationException
     *
     * @return Client
     */
    protected function makeConnection(string $name): Client
    {
        $config = $this->getConfig($name);

        return $this->factory->make($config);
    }

    /**
     * Get the configuration for a named connection.
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getConfig(string $name)
    {
        $connections = $this->app['config']['elasticsearch.connections'];

        if (null === $config = Arr::get($connections, $name)) {
            throw new InvalidArgumentException("Elasticsearch connection [$name] not configured.");
        }

        return $config;
    }

    /**
     * Return all the created connections.
     *
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws AuthenticationException
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->connection(), $method], $parameters);
    }
}
