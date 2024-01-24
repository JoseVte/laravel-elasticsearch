<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Console\Command;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;

final class IndexExistsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'laravel-elasticsearch:utils:index-exists
                            {index-name : The index name}';

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        Client $client
    ) {
        $this->client = $client;

        parent::__construct();
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function handle(): int
    {
        $indexName = $this->argument('index-name');

        if ($indexName === null ||
            ! is_string($indexName) ||
            $indexName === ''
        ) {
            $this->output->writeln(
                '<error>Argument index-name must be a non empty string.</error>'
            );

            return self::FAILURE;
        }

        if ($this->client->indices()->exists([
            'index' => $indexName,
        ])) {
            $this->output->writeln(
                sprintf(
                    '<info>Index %s exists.</info>',
                    $indexName
                )
            );

            return self::SUCCESS;
        }

        $this->output->writeln(
            sprintf(
                '<comment>Index %s doesn\'t exists.</comment>',
                $indexName
            )
        );

        return self::SUCCESS;
    }
}
