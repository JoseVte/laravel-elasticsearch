<?php

namespace MailerLite\LaravelElasticsearch;

use Monolog\Logger;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Elastic\Elasticsearch\Client;
use Monolog\Handler\StreamHandler;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

class Factory
{
    /**
     * Map configuration array keys with ES ClientBuilder setters.
     *
     * @var array
     */
    protected $configMappings = [
        'sslVerification' => 'setSSLVerification',
        'retries' => 'setRetries',
        'httpHandler' => 'setHandler',
        'nodePool' => 'setNodePool',
        'connectionSelector' => 'setSelector',
        'serializer' => 'setSerializer',
        'connectionFactory' => 'setConnectionFactory',
        'endpoint' => 'setEndpoint',
        'namespaces' => 'registerNamespace',
    ];

    /**
     * Make the Elasticsearch client for the given named configuration, or
     * the default client.
     *
     * @param array $config
     *
     * @throws AuthenticationException
     *
     * @return Client
     */
    public function make(array $config): Client
    {
        return $this->buildClient($config);
    }

    /**
     * Build and configure an Elasticsearch client.
     *
     * @param array $config
     *
     * @throws AuthenticationException
     *
     * @return Client
     */
    protected function buildClient(array $config): Client
    {
        $clientBuilder = ClientBuilder::create();

        // Configure hosts
        $hosts = [];
        foreach ($config['hosts'] as $host) {
            $hosts[] = http_build_url(Arr::only($host, ['scheme', 'host', 'port']));

            if (! empty($host['user']) && ! empty($host['pass'])) {
                $clientBuilder->setBasicAuthentication($host['user'], $host['pass']);
            }
        }

        $clientBuilder->setHosts($hosts);

        // Configure logging
        if (Arr::get($config, 'logging')) {
            $logObject = Arr::get($config, 'logObject');
            $logPath = Arr::get($config, 'logPath');
            $logLevel = Arr::get($config, 'logLevel');
            if ($logObject && $logObject instanceof LoggerInterface) {
                $clientBuilder->setLogger($logObject);
            } elseif ($logPath && $logLevel) {
                $handler = new StreamHandler($logPath, $logLevel);
                $logObject = new Logger('log');
                $logObject->pushHandler($handler);
                $clientBuilder->setLogger($logObject);
            }
        }

        // Set additional client configuration
        foreach ($this->configMappings as $key => $method) {
            $value = Arr::get($config, $key);
            if (is_array($value)) {
                foreach ($value as $vItem) {
                    $clientBuilder->$method($vItem);
                }
            } elseif ($value !== null) {
                $clientBuilder->$method($value);
            }
        }

        // Build and return the client
        if (! empty($host['api_id']) && ! empty($host['api_key'])) {
            $clientBuilder->setApiKey($host['api_id'], $host['api_key']);
        }

        return $clientBuilder->build();
    }
}
